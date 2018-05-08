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

    public function __construct(Request $Request){
      $this->base_url_oauth = 'https://api.resourceguruapp.com/';
      $this->client_id_oauth = '351065fc63b6ecf966852c655b375ba0f426251d38455e88a488de2a71118821';
      $this->client_secret_oauth = '325a0cdb5222355f15c66270c83cb23d481facab9c50f66f60826717c1accba0';
      $this->client_name_oauth = 'ionz';
      $this->client = false;
      $this->request = $Request;
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
      
      //retorna os dados do usuário
      $me = $this->getMe();
      
      //verifica se o usuario foi retornado
      if(!$me) return $this->logout();

      //dados da view
      $response = array();
      $response['user'] = $me->json();

      //retorna o(s) resources esquivalentes para esse usuário
      $resources = $this->getResourceMe();

      if(!$resources) $response['resources'] = false;

      //filtra apenas o resouce do usuario
      $resourcesCollect = collect($resources->json()['resources'])->where('name', $response['user']['first_name']. ' ' . $response['user']['last_name']);
      $response['resources'] = $resourcesCollect->toArray();

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
      if(!$me) return view('login')->withErrors($validator);

      //salva os dados de acesso na sessao
      $this->request->session()->put('client', $this->configClient);

      //redireciona para a home
      return redirect('/');
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
    private function getResourceMe(){
      try{
        $resources = $this->client->get($this->base_url_oauth.'v1/'.$this->client_name_oauth.'/reports/resources?start_date='.date('Y-m-d').'&end_date='.date('Y-m-d'));
        if($resources->getStatusCode()!=200) return false;
        return $resources;
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


    // public function getMe(){
    //   $me = $this->client->get('https://api.resourceguruapp.com/v1/me');
    //   $resource = $this->client->get('https://api.resourceguruapp.com/v1/'.$this->client_name_oauth.'/reports/resources?start_date=2018-05-08&end_date=2018-05-08');
    //   $projects = $this->client->get('https://api.resourceguruapp.com/v1/'.$this->client_name_oauth.'/reports/projects?start_date=2018-05-08&end_date=2018-05-08');
    //   dump($me->json());
    //   dump($resource->json());
    // }


}
