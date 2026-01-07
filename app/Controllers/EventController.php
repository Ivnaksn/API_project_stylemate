<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\UserModel;
use App\Libraries\WeatherAPI;
use CodeIgniter\RESTful\ResourceController;

class EventController extends ResourceController
{
    protected $modelName = 'App\Models\EventModel';
    protected $format    = 'json';

    
    private function getInternalUserId()
    {
        
        if (isset($this->request->user)) {
           
            return $this->request->user->user_id; 
        }
        return null;
    }

 
    public function create()
    {
        $internalUserId = $this->getInternalUserId();

        if (!$internalUserId) {
            return $this->failUnauthorized('Gagal mengidentifikasi User. Silakan login ulang.');
        }

        $rules = [
            'name'     => 'required',
            'date'     => 'required|valid_date',
            'location' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $location  = $this->request->getVar('location');
        $dateEvent = $this->request->getVar('date');

       
        $weatherAPI = new WeatherAPI();
        $weather    = $weatherAPI->getWeather($location, $dateEvent);

        $data = [
            'user_id'           => $internalUserId,
            'name'              => $this->request->getVar('name'),
            'description'       => $this->request->getVar('description'),
            'date'              => $dateEvent,
            'location'          => $location,
            'weather_temp'      => $weather['temp'] ?? null,
            'weather_condition' => $weather['condition'] ?? null
        ];

        
        $eventId = $this->model->insert($data);

        if ($eventId) {
            return $this->respondCreated([
                'success'  => true,
                'message'  => 'Event berhasil dibuat',
                'data'     => $data
            ]);
        }

        return $this->fail('Gagal menyimpan event ke database');
    }

    
    public function index()
    {
        $internalUserId = $this->getInternalUserId();

        if (!$internalUserId) {
            return $this->failUnauthorized('User ID tidak terbaca');
        }

        $events = $this->model
            ->where('user_id', $internalUserId)
            ->orderBy('date', 'ASC')
            ->findAll();

        return $this->respond([
            'success' => true,
            'events'  => $events 
        ]);
    }

    public function delete($id = null)
    {
        $internalUserId = $this->getInternalUserId();

        if (!$internalUserId) {
            return $this->failUnauthorized('Silakan login ulang.');
        }

        $event = $this->model->find($id);

        if (!$event) {
            return $this->failNotFound('Event tidak ditemukan');
        }
        if ($event['user_id'] != $internalUserId) {
            return $this->failForbidden('Anda tidak diizinkan menghapus event ini.');
        }

        if ($this->model->delete($id)) {
            return $this->respondDeleted([
                'success' => true,
                'message' => 'Event berhasil dihapus'
            ]);
        }

        return $this->fail('Gagal menghapus data dari database');
    }
}