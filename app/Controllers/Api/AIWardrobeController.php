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
                return $this->fail(['success' => false, 'message' => 'Image is required'], 400);
            }

            $analysis = $this->geminiService->analyzeClothingImage($imageBase64);

            return $this->respond([
                'success' => true,
                'message' => 'Image analyzed successfully',
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return $this->fail(['success' => false, 'message' => 'Failed to analyze image', 'error' => $e->getMessage()], 500);
        }
    }

    public function generateOutfits()
    {
        try {
            $requestData = $this->request->getJSON(true) ?? [];
            
            $userId = $requestData['userId'] ?? null;
            $occasion = $requestData['occasion'] ?? 'daily';
            $weather = $requestData['weather'] ?? 'warm';

            if (empty($userId)) {
                return $this->fail(['success' => false, 'message' => 'User ID is required'], 400);
            }

            $wardrobeItems = $this->wardrobeModel->getUserWardrobe($userId);

            if (empty($wardrobeItems)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Lemari kamu kosong! Upload foto baju dulu ya.',
                    'recommendations' => [],
                ], 404); 
            }

            $formattedItems = array_map(function($item) {
                return [
                    'id'          => $item['id'],
                    'name'        => $item['name'],
                    'categoryId'  => $item['category_id'], 
                    'color'       => $item['color'],      
                    'style'       => $item['style'],
                    'imageUrl'    => $item['image_url'],
                ];
            }, $wardrobeItems);

            $filters = [
                'occasion'    => $occasion,
                'weather'     => $weather,
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
            return $this->fail(['success' => false, 'message' => 'gagal memberikan rekomendasi', 'error' => $e->getMessage()], 500);
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