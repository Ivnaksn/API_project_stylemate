<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

class GeminiVisionService
{
    protected $apiKey;
    protected $client;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        
        $this->apiKey = getenv('GEMINI_API_KEY');
        
        
        if (!$this->apiKey) {
            log_message('error', 'Gemini API Key is missing in .env file');
        }

        $this->client = Services::curlrequest([
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ]);
    }

    public function analyzeClothingImage($base64Image)
    {
        if (strpos($base64Image, ',') !== false) {
            $base64Image = explode(',', $base64Image)[1];
        }

        $endpoint = $this->baseUrl . 'gemini-1.5-flash:generateContent?key=' . $this->apiKey;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Analyze this clothing item. Return JSON with keys: category (e.g., shirt, pants), color (hex codes), style (casual/formal), pattern (plain/striped)."],
                        [
                            'inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->sendRequest($endpoint, $payload);
    }

    public function generateOutfitRecommendations($wardrobeItems, $filters)
    {
        $endpoint = $this->baseUrl . 'gemini-1.5-flash:generateContent?key=' . $this->apiKey;

        $itemsJson = json_encode($wardrobeItems);
        $prompt = "Act as a fashion stylist. Based on this wardrobe: $itemsJson. " .
                  "Create an outfit for: " . json_encode($filters) . ". " .
                  "Return valid JSON array of outfit combinations.";

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ];

        return $this->sendRequest($endpoint, $payload);
    }

    private function sendRequest($url, $body)
    {
        try {
            $response = $this->client->post($url, ['json' => $body]);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Gemini API Error: ' . $response->getBody());
            }

            $data = json_decode($response->getBody(), true);
            return $this->parseGeminiResponse($data);

        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    private function parseGeminiResponse($data)
    {
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'];
            $rawText = str_replace(['```json', '```'], '', $rawText);
            return json_decode(trim($rawText), true) ?? $rawText;
        }
        return null;
    }
}