<?php

namespace App\Libraries;

class AIRecommendation
{
    private $apiKey = 'AIzaSyCFwkua5NirMGfQicPpsIS8WEgczWW8M_8'; 

    
    public function analyzeImage($imagePath)
    {
        
        $imageData = base64_encode(file_get_contents($imagePath));

        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey;

        
        $prompt = "Identify this clothing item. Return ONLY a JSON object with these keys: 
                   'color' (dominant color), 
                   'style' (formal, casual, sport, or elegant), 
                   'weather' (hot, cold, or rainy), 
                   'material' (cotton, wool, etc.).";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt],
                        [
                            "inline_data" => [
                                "mime_type" => "image/jpeg",
                                "data" => $imageData
                            ]
                        ]
                    ]
                ]
            ]
        ];

        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return null;
        }

        
        return $this->parseGeminiResponse($response);
    }

    /**
     * 
     * 
     * @param array $items 
     * @param array|object $event 
     * @return array 
     */
    public function generateRecommendations($items, $event)
    {
        $eventName = is_array($event) ? $event['name'] : $event->name;
        $eventTemp = is_array($event) ? ($event['weather_temp'] ?? 28) : ($event->weather_temp ?? 28);
        $eventDate = is_array($event) ? ($event['date'] ?? date('Y-m-d')) : ($event->date ?? date('Y-m-d'));
        
        
        $itemsList = $this->formatItemsForPrompt($items);
        
        
        $prompt = $this->buildRecommendationPrompt($itemsList, $eventName, $eventTemp, $eventDate);
        
        try {
            
            $geminiResponse = $this->callGeminiTextAPI($prompt);
            
            if ($geminiResponse) {
                return $geminiResponse;
            }
        } catch (\Exception $e) {
            log_message('error', 'Gemini API Error: ' . $e->getMessage());
        }
        
        
        return $this->generateDummyRecommendations($items, $event);
    }
    
    
    private function formatItemsForPrompt($items)
{
    $formatted = [];
    foreach ($items as $item) {
        $formatted[] = [
            'id'       => $item['item_id'] ?? $item['id'],
            'name'     => $item['name'],
            'category' => $item['category_name'] ?? $item['category'] ?? 'unknown',
            'color'    => $item['color'] ?? 'unknown',
            'style'    => $item['detected_style'] ?? 'casual',
            'image'    => $item['image_url'] ?? $item['image'] ?? '',
        ];
    }
    return $formatted;
}
    
    
    private function buildRecommendationPrompt($items, $eventName, $temperature, $date)
    {
        $itemsJson = json_encode($items, JSON_PRETTY_PRINT);
        
        return "You are a professional fashion stylist. Based on the following wardrobe items and event details, create 3 outfit recommendations.

WARDROBE ITEMS:
$itemsJson

EVENT DETAILS:
- Event: $eventName
- Temperature: {$temperature}°C
- Date: $date

TASK:
Generate 3 different outfit combinations. For each outfit:
1. Select 3-4 items. CRITICAL: Ensure each outfit is a complete look (e.g., must include 1 Top, 1 Bottom, and 1 Footwear if the categories are available).
2. Explain why this combination works for the event.
3. Provide weather-appropriate styling tips.


Return ONLY a JSON array with this exact structure:
[
  {
    \"id\": 1,
    \"reason\": \"Brief explanation why this outfit works\",
    \"weather_tip\": \"Practical weather advice\",
    \"items\": [
      {\"id\": item_id, \"name\": \"item name\", \"categoryName\": \"category\", \"image\": \"\"}
    ]
  }
]

IMPORTANT: 
- Each outfit must use different item combinations
- Consider color coordination, style matching, and weather appropriateness
- Return ONLY valid JSON, no markdown formatting";
    }
    
    
    private function callGeminiTextAPI($prompt)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey;
        
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 2048,
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($err) {
            log_message('error', 'CURL Error: ' . $err);
            return null;
        }
        
        if ($httpCode !== 200) {
            log_message('error', 'Gemini API HTTP ' . $httpCode . ': ' . $response);
            return null;
        }
        
        return $this->parseGeminiResponse($response);
    }
    
    
    private function generateDummyRecommendations($items, $event)
    {
        $eventName = is_array($event) ? $event['name'] : $event->name;
        $eventTemp = is_array($event) ? ($event['temp'] ?? 28) : ($event->temp ?? 28);
        
        $recommendations = [];
        
        
        $formattedItems = array_map(function($item) {
    return [
        'id' => $item['item_id'] ?? $item['id'],
        'name' => $item['name'],
        'categoryName' => $item['category'] ?? 'Pakaian',
        'image' => $item['image_url'] ?? $item['image'] ?? '', 
    ];
}, $items);
        
        if (empty($formattedItems)) {
            return $recommendations;
        }
        
        
        for ($i = 1; $i <= 3; $i++) {
            $count = count($formattedItems);
            $startIndex = ($i - 1) % $count;
            $selectedItems = [];
            
            
            for ($j = 0; $j < min(4, $count); $j++) {
                $index = ($startIndex + $j) % $count;
                $selectedItems[] = $formattedItems[$index];
            }
            
            $reasons = [
                1 => "Outfit kasual yang sempurna untuk {$eventName}. Kombinasi yang nyaman dan stylish.",
                2 => "Pilihan formal yang elegan untuk {$eventName}. Tampil profesional dan percaya diri.",
                3 => "Smart casual yang versatile untuk {$eventName}. Cocok untuk berbagai situasi.",
            ];
            
            $weatherTip = $eventTemp >= 30 
                ? "Cuaca panas ({$eventTemp}°C). Pilih bahan yang breathable dan warna terang."
                : ($eventTemp >= 25 
                    ? "Cuaca hangat ({$eventTemp}°C). Nyaman untuk outfit ringan."
                    : "Cuaca sejuk ({$eventTemp}°C). Bawa jaket ringan untuk antisipasi.");
            
            $recommendations[] = [
                'id' => $i,
                'reason' => $reasons[$i],
                'weather_tip' => $weatherTip,
                'items' => $selectedItems
            ];
        }
        
        return $recommendations;
    }

    
    private function parseGeminiResponse($response)
    {
        $result = json_decode($response, true);
        
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return null;
        }
        
        $text = $result['candidates'][0]['content']['parts'][0]['text'];

        // Menghapus markdown jika ada
        $cleanJson = str_replace(['```json', '```'], '', $text);
        $cleanJson = trim($cleanJson);
        
        $decoded = json_decode($cleanJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'JSON Parse Error: ' . json_last_error_msg());
            log_message('error', 'Raw text: ' . $text);
            return null;
        }
        
        return $decoded;
    }
}