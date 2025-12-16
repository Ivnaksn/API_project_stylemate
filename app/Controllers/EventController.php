<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Libraries\WeatherAPI;
use CodeIgniter\RESTful\ResourceController;

class EventController extends ResourceController
{
    protected $modelName = 'App\Models\EventModel';
    protected $format = 'json';
        private function getUserId()
    {
        $rawUser = $this->request->user ?? null;
        $user = (array) $rawUser;
        return $user['user_id'] ?? $user['id'] ?? $user['uid'] ?? null;
    }
    public function create()
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->failUnauthorized('User ID tidak terbaca');
        $rules = [
            'name'     => 'required',
            'date'     => 'required|valid_date',
            'location' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }
        $location = $this->request->getVar('location');
        $dateEvent = $this->request->getVar('date');
        $weatherAPI = new WeatherAPI();
        $weather = $weatherAPI->getWeather($location, $dateEvent);

        $data = [
            'user_id'           => $userId,
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
                'event_id' => $eventId,
                'message'  => 'Event berhasil dibuat',
                'weather_info' => $weather ? "Prediksi cuaca: {$weather['condition']} ({$weather['temp']}°C)" : "Data cuaca tidak tersedia",
                'data' => $data
            ]);
        }
        
        return $this->fail('Gagal membuat event');
    }

    public function index()
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->failUnauthorized('User ID tidak terbaca');
        
        $events = $this->model
            ->where('user_id', $userId)
            ->orderBy('date', 'ASC')
            ->findAll();
        
        return $this->respond([
            'success' => true,
            'events' => $events
        ]);
    }
}