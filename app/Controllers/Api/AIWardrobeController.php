<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\IncomingRequest;
use App\Libraries\GeminiVisionService;
use App\Models\WardrobeItemModel;
use App\Models\WardrobeModel;

/**
 * @property IncomingRequest $request
 */
class AIWardrobeController extends ResourceController
{
    protected $format = 'json';
    protected $geminiService;
    protected $wardrobeModel;

    public function __construct()
    {
        $this->geminiService = new GeminiVisionService();
        $this->wardrobeModel = new WardrobeModel();
        
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

   
    public function analyzeClothing()
    {
        try {
            $imageBase64 = $this->request->getPost('image');
            
            if (empty($imageBase64)) {
                return $this->fail([
                    'success' => false,
                    'message' => 'Image is required',
                ], 400);
            }

            
            $analysis = $this->geminiService->analyzeClothingImage($imageBase64);

            return $this->respond([
                'success' => true,
                'message' => 'Image analyzed successfully',
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Analyze Error: ' . $e->getMessage());
            
            return $this->fail([
                'success' => false,
                'message' => 'Failed to analyze image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function generateOutfits()
    {
        try {
            $requestData = $this->request->getJSON(true) ?? [];
            
            $userId = $requestData['userId'] ?? null;
            $occasion = $requestData['occasion'] ?? 'daily';
            $weather = $requestData['weather'] ?? 'warm';
            $temperature = $requestData['temperature'] ?? 25;
            $formality = $requestData['formality'] ?? 'casual';

            if (empty($userId)) {
                return $this->fail([
                    'success' => false,
                    'message' => 'User ID is required',
                ], 400);
            }

            $wardrobeItems = $this->wardrobeModel->getUserWardrobe($userId);

            if (empty($wardrobeItems)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Lemari kamu kosong! Upload foto baju dulu.', 
                    'recommendations' => [],
                ], 404); 
            }

            
            $formattedItems = array_map(function($item) {
                return [
                    'id' => $item['item_id'],
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'colors' => json_decode($item['colors'], true),
                    'style' => $item['style'],
                    'pattern' => $item['pattern'],
                    'imageUrl' => $item['image_url'],
                ];
            }, $wardrobeItems);

            $filters = [
                'occasion' => $occasion,
                'weather' => $weather,
                'temperature' => $temperature,
                'formality' => $formality,
            ];

            $recommendations = $this->geminiService->generateOutfitRecommendations(
                $formattedItems,
                $filters
            );

            return $this->respond([
                'success' => true,
                'message' => 'AI recommendations generated',
                'count' => count($recommendations),
                'recommendations' => $recommendations,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Generate Error: ' . $e->getMessage());
            
            return $this->fail([
                'success' => false,
                'message' => 'Failed to generate recommendations',
                'error' => $e->getMessage(),
            ], 500);
        }
        
    }
    

    public function health()
    {
        return $this->respond([
            'success' => true,
            'message' => 'AI Service is running',
            'service' => 'Gemini Vision API',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}