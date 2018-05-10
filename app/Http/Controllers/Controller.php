<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Oauth2\Oauth2Subscriber;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Collection;
use App\Booking;
use DateTime;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $client;
    protected $base_url_oauth;
    protected $client_id_oauth;
    protected $client_secret_oauth;
    protected $client_name_oauth;
    protected $Request;
    protected $configClient;
    protected $booking;

    public function __construct(Request $Request, Booking $Booking){
      $this->base_url_oauth = 'https://api.resourceguruapp.com/';
      $this->client_id_oauth = '351065fc63b6ecf966852c655b375ba0f426251d38455e88a488de2a71118821';
      $this->client_secret_oauth = '325a0cdb5222355f15c66270c83cb23d481facab9c50f66f60826717c1accba0';
      $this->client_name_oauth = 'ionz';
      $this->client = false;
      $this->request = $Request;
      $this->booking = $Booking;
    }

    /**
     * home
     *
     * Retorna todos os dados do usuário e apresenta na view
     *
     * @return void
     */
    public function home(){
      
      $this->getClientSession();
      
      //dados da view
      $response = array();

      //retorna o(s) resources esquivalentes para esse usuário
      $resources = $this->getResourceMe();

      //verifica se a requisição foi feita com sucesso
      if(!$resources) return $this->logout();

      $response = $resources->json();

      return view('home', $response);

    }

    /**
     * login
     *
     * Verifica os dados e loga o usuário na API e no Laravel
     *
     * @return void
     */
    public function login(){

      if(!request()->isMethod('post')) return view('login');
      
      $request = new LoginRequests();
      //retorna os dados enviados
      $fields = request()->all();
      //verifica as validações
      $validator = validator()->make($fields, $request->rules());
      
      //envia o e-mail e senha, para retornar o token de acesso
      $getOauthToken = $this->getOauthToken($fields['email'], $fields['password']);
     
      //retorna os dados do usuário
      $me = $this->getMe();

      //verifica se os dados estão corretos
      if(!$me) return view('login')->withErrors(array(['Invalid login']));

      //salva os dados de acesso na sessao e o nome do usuário
      $this->request->session()->put('client', $this->configClient);
      $this->request->session()->put('name', $me->json()['first_name']);

      //redireciona para a home
      return redirect('/');
    }

    /**
     * resource
     *
     * Retorna todos os dados do resource e seus projetos do dia
     *
     * @return void
     */
    public function resource($idResource){

      $this->getClientSession();

      //retorna os dados enviados
      $fields = request()->all();
      
      //dados da view
      $response = array();
      $dataSave = array();
      $idsResources = array();
      $response['projects'] = array();

      //verifica se algum filtro foi aplicado
      if(isset($fields['date']) && $fields['date']!=''){
        $response['current_date'] = $fields['date'];
        
        if(isset($fields['day']) && $fields['day']=='-1'){
          $response['current_date'] = date('Y-m-d', strtotime('-1 days', strtotime($response['current_date'])));
        }else if(isset($fields['day']) && $fields['day']=='+1'){
          $response['current_date'] = date('Y-m-d', strtotime('+1 days', strtotime($response['current_date'])));
        }else if(isset($fields['day']) && $fields['day']=='hoje'){
          $response['current_date'] = date('Y-m-d');
        }

      }else{
        $response['current_date'] = date('Y-m-d');
      }

      //retona dados do resource
      $resourceMe = $this->getResourceMe($idResource);
      $response['resource'] = $resourceMe->json();

      //retorna o(s) agendamentos do ID resource
      $bookings = $this->getBookings($idResource, $response['current_date']);

      //verifica se a requisição foi feita com sucesso
      if(!$bookings) return $this->logout();

      //agrupa todos os IDs de projetos
      $idsProjects = collect($bookings->json())->groupBy('project_id');

      //retorna os dados de todos os projetos
      foreach ($idsProjects as $key => $idProject) {
        $response['projects'][$key] = $this->getProject($key)->json();
      }

      $response['bookings'] = $bookings->json();

      //Trata os destalhes de cada agendamento e trata os dados para salvar no banco de dados
      foreach ($response['bookings'] as $key => $booking) {
        $response['bookings'][$key]['details'] = $this->returnLink($booking['details']);
        
        $booking['data_project'] = $response['projects'][$booking['project_id']];

        $dataSave[] = array(
          'id' => $booking['id'],
          'client_id' => $booking['client_id'],
          'project_id' => $booking['project_id'],
          'resource_id' => $booking['resource_id'],
          'data' => json_encode($booking)
        );

        $idsResources[] = $booking['id'];

      }

      //salvo todos os bookings no banco de dados
      $this->booking->insertMany($dataSave);

      //retorna os dados salvos do resource
      $response['resource_saveds'] = $this->booking->getBookingIdIn($idsResources);

      return view('resource', $response);

    }

    
    /**
     * logout
     *
     * Deleta todas as variaveis de sessão e redireciona para login
     *
     * @return void
     */
    public function logout(){
      $this->request->session()->flush();
      return redirect('/login');
    }


    /**
     * getOauthToken
     *
     * Recebe as credenciais de acesso e retorna o token para utilizar od endpoints da API
     *
     * @param string $username   A parameter description.
     * @param string $password   Another parameter description.
     *
     * @return void
     */
    private function getOauthToken($username, $password){
      $oauth2Client = new Client(['base_url' => $this->base_url_oauth]);
      $config = [
          'username' => $username,
          'password' => $password,
          'client_id' => $this->client_id_oauth,
          'client_secret' => $this->client_secret_oauth,
          'token_url' => 'oauth/token'
      ];
      $this->configClient = [Crypt::encryptString($username), Crypt::encryptString($password)];
      $token = new PasswordCredentials($oauth2Client, $config);
      $refreshToken = new RefreshToken($oauth2Client, $config);
      $oauth2 = new Oauth2Subscriber($token, $refreshToken);
      $client = new Client([
          'defaults' => [
              'auth' => 'oauth2',
              'subscribers' => [$oauth2],
          ],
      ]);
      $this->client = $client;
    }

    /**
     * getMe
     *
     * verifica se a credencais estão corretas e retorna os dados do usuário 
     *
     * @return boolean
     */
    private function getMe(){
      try{
        $me = $this->client->get($this->base_url_oauth.'v1/me');
        if($me->getStatusCode()!=200) return false;
        return $me;
      } catch (ClientException $e) {
        return false;
        exit();
      }
    }

    /**
     * getResourceMe
     *
     * Retorna o resourve equivalente ao usuário
     *
     * @return boolean
     */
    private function getResourceMe($idResource = false, $dateFilter = false){
      try{
        
        $date = date('Y-m-d');
        if($dateFilter) $date = $dateFilter;

        $endPoint = '/reports/resources?start_date='.$date.'&end_date='.$date;
        if($idResource) $endPoint = '/reports/resources/'.$idResource.'?start_date='.$date.'&end_date='.$date;

        $resources = $this->client->get($this->base_url_oauth.'v1/'.$this->client_name_oauth.$endPoint);
        if($resources->getStatusCode()!=200) return false;
        return $resources;

      } catch (ClientException $e) {
        return false;
        exit();
      }
    }


    /**
     * getBookings
     *
     * Retorna os agendamentos por resource
     *
     * @return boolean
     */
    private function getBookings($idResource, $dateFilter = false){
      try{
        
        $date = date('Y-m-d');
        if($dateFilter) $date = $dateFilter;

        $endPoint = '/resources/'.$idResource.'/bookings?start_date='.$date.'&end_date='.$date;

        $resources = $this->client->get($this->base_url_oauth.'v1/'.$this->client_name_oauth.$endPoint);
        if($resources->getStatusCode()!=200) return false;
        return $resources;

      } catch (ClientException $e) {
        return false;
        exit();
      }
    }


    /**
     * getProject
     *
     * Retorna os dados do projeto por ID
     *
     * @return boolean
     */
    private function getProject($idProject, $dateFilter = false){
      try{
        
        $date = date('Y-m-d');
        if($dateFilter) $date = $dateFilter;

        $endPoint = '/projects/'.$idProject.'?start_date='.$date.'&end_date='.$date;

        $projects = $this->client->get($this->base_url_oauth.'v1/'.$this->client_name_oauth.$endPoint);
        if($projects->getStatusCode()!=200) return false;
        return $projects;

      } catch (ClientException $e) {
        return false;
        exit();
      }
    }


    /**
     * getClientSession
     *
     * retorna a sessao com as credenciais e monta o config para obter o token
     *
     * @return boolean
     */
    private function getClientSession(){
      if(!$this->request->session()->has('client')) return false;
      $clientSession = $this->request->session()->get('client');
      $this->getOauthToken(Crypt::decryptString($clientSession[0]), Crypt::decryptString($clientSession[1]));
    }


    /**
     * returnLink
     *
     * Procura url na string e transforma em lnk
     *
     * @return string
     */
    private function returnLink($text){
          $text = ' ' . html_entity_decode( $text );
          // Full-formed links
          $text = preg_replace('#(((f|ht){1}tps?://)[-a-zA-Z0-9@:%_\+.~\#?&//=]+)#i', '<a href="\\1" target=_blank>\\1</a>', $text);
          // Links without scheme prefix (i.e. http://)
          $text = preg_replace('#([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~\#?&//=]+)#i', '\\1<a href="http://\\2" target=_blank>\\2</a>', $text);
          // E-mail links (mailto)
          $text = preg_replace('#([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})#i', '<a href="mailto:\\1" target=_blank>\\1</a>', $text);
          return $text;
    }

    /**
     * updateStatus
     *
     * Atauliza o status do booking
     *
     * @return string
     */
    public function updateStatus(){
      //retorna os dados enviados
      $fields = request()->all();
      
      //retorna os dados do booking
      $booking = $this->booking->getBooking($fields['id']);
      if(!$booking) return 'ERROR';

      //verifica qual o novo status
      $status = 'NEW';
      if($booking->status=='NEW') $status = 'FINALIZED';

      //atauliza o status
      $bookingUpdate = $this->booking->updateStatus($fields['id'], $status);

      //verifica a requisição
      if(!$bookingUpdate) return $booking->status;

      return $status;

    }


    /**
     * updateTimer
     *
     * Atauliza o timer do booking
     *
     * @return string
     */
    public function updateTimer(){
      //retorna os dados enviados
      $fields = request()->all();
      
      //retorna os dados do booking
      $booking = $this->booking->getBooking($fields['id']);
      if(!$booking) return 'ERROR';

      $timer = 'OFF';
      $start_timer = $booking->start_timer;
      $end_timer = date('Y-m-d H:i:s');

      if($booking->timer=='OFF') {
        $timer = 'ON';
        $start_timer = date('Y-m-d H:i:s');
        $end_timer = date('Y-m-d H:i:s');
      }

      //Calcula a diferenca entre duas datas e retornas os minutos e segundos
      $timer_count = $booking->timer_count + $this->calcMinutes($start_timer, $end_timer);

      //atauliza o timer
      $bookingUpdateTimer = $this->booking->updateTimer($fields['id'], $timer, $start_timer, $end_timer, $timer_count);

      //verifica a requisição
      if(!$bookingUpdateTimer) return array('response'=>$booking->timer, 'timer_count'=>$timer_count);

      return array('response'=>$timer, 'timer_count'=>Self::minutosToHour($timer_count));

    }


    /**
     * calcMinutes
     *
     * Calcula a diferenca de minutos entre duas datas
     *
     * @return string
     */
    public function calcMinutes($data1, $data2){
      $datetime1 = new DateTime($data1);
      $datetime2 = new DateTime($data2);
      $intervalo = $datetime1->diff($datetime2);
      $days = ((int)$intervalo->days)*1440;
      $hour = ((int)$intervalo->h)*60;
      $min = (int)$intervalo->i;
      return $days+$hour+$min;
    }

    /**
     * minutosToHour
     *
     * trasnforma minutos em horas
     *
     * @return string
     */
    static public function minutosToHour($minutes){
      $hora = floor($minutes/60);
      $resto = $minutes%60;
      if($resto<10) $resto = '0'.$resto;
      return $hora.':'.$resto;
    }


}
