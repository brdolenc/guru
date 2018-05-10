<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_id', 'project_id', 'resource_id', 'data', 'status', 'start_timer', 'end_timer', 'status_updated_at'
    ];

    /**
     * Inserir booking
     *
     * @var $_POST
     *
     * @return object
     */
    public function insertMany($data){
    	//verifica entradas já existentes
    	foreach ($data as $key => $booking) if(Self::where('id', $booking['id'])->count()>0) unset($data[$key]);
		//erro no cadastro
        if(!Self::insert($data)) return false;
		return true;	      
    }

    /**
     * retorna booking por id
     *
     * @var int
     *
     * @return object
     */
    public function getBooking($id){
    	return Self::find($id);
    }

    /**
     * retorna bookings por ids
     *
     * @var int
     *
     * @return array
     */
    public function getBookingIdIn($ids){
    	$response = Self::whereIn('id', $ids)->get()->keyBy('id');
    	if(!$response) return false;
    	return $response->toArray();
    }

    /**
     * atualiza o status do booking
     *
     * @var int
     *
     * @return object
     */
    public function updateStatus($id, $status){
    	return Self::where('id', $id)->update(['status' => $status, 'status_updated_at' => date('Y-m-d H:i:s')]);     
    }


    /**
     * atualiza o timer do booking
     *
     * @var int
     *
     * @return object
     */
    public function updateTimer($id, $timer, $start_timer, $end_timer, $timer_count){
    	return Self::where('id', $id)->update(['timer' => $timer, 'start_timer' => $start_timer, 'end_timer' => $end_timer, 'timer_count' => $timer_count]);     
    }
}
