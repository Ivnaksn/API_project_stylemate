<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\WardrobeItemModel;
use App\Models\OutfitModel;
use App\Models\OutfitItemModel;
use App\Libraries\AIRecommendation;
use CodeIgniter\RESTful\ResourceController;

class RecommendationController extends ResourceController
{
    protected $format = 'json';
    
    private function getUserId()
    {
        if (!isset($this->request->user)) {
            return null;
        }
        $user = $this->request->user;
        if (is_object($user)) {
            return $user->user_id ?? $user->id ?? null;
        }
        if (is_array($user)) {
            return $user['user_id'] ?? $user['id'] ?? null;
        }
        return null;
    }

    public function getRecommendations($eventId)
    {
        $userId = $this->getUserId();
        
        
        if (!$userId) {
            return $this->failUnauthorized('User tidak terautentikasi');
        }

        $eventModel = new EventModel();
        $event = $eventModel
            ->where('event_id', $eventId)
            ->where('user_id', $userId)
            ->first();
        
        if (!$event) {
            return $this->failNotFound('Event tidak ditemukan');
        }
        
        
        $wardrobeModel = new WardrobeItemModel();
        $items = $wardrobeModel
            ->select('wardrobe_items.*, categories.name as category_name, ai_analyses.weather_suitable, ai_analyses.material, ai_analyses.detected_style')
            ->join('categories', 'categories.category_id = wardrobe_items.category_id') // Join untuk dapatkan Nama Kategori
            ->join('ai_analyses', 'wardrobe_items.item_id = ai_analyses.item_id', 'left')
            ->where('wardrobe_items.user_id', $userId)
            ->get()
            ->getResultArray(); 
        
        if (empty($items)) {
            return $this->failNotFound('Lemari pakaian Anda kosong.');
        }
        
        try {
            $ai = new AIRecommendation();
            $recommendations = $ai->generateRecommendations($items, $event);
            
            return $this->respond([
                'status' => 'success',
                'event' => $event,
                'recommendations' => $recommendations
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'AI Recommendation Error: ' . $e->getMessage());
            $dummyRecommendations = $this->generateDummyRecommendations($items, $event);
            return $this->respond([
                'status' => 'success',
                'event' => $event,
                'recommendations' => $dummyRecommendations,
                'note' => 'Using fallback'
            ]);
        }
    }
    
    private function generateDummyRecommendations($items, $event)
    {
        $recommendations = [];
        $formattedItems = array_map(function($item) {
            return [
                'id' => $item['item_id'],
                'name' => $item['name'],
                'categoryName' => $item['category_name'] ?? 'Pakaian', 
                'image' => $item['image_url'] ?? $item['image'] ?? '',
            ];
        }, $items);
        
        for ($i = 1; $i <= 3; $i++) {
            $selectedItems = array_slice($formattedItems, 0, min(3, count($formattedItems)));
            $recommendations[] = [
                'id' => $i,
                'reason' => "Outfit #$i - Pilihan terbaik untuk " . ($event['name'] ?? 'acara ini'),
                'weather_tip' => 'Suhu ' . ($event['weather_temp'] ?? '28') . '°C',
                'items' => $selectedItems
            ];
        }
        return $recommendations;
    }

    public function saveOutfit() {  }
}