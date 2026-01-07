<?php

namespace App\Controllers;

use App\Models\OutfitModel;

class OutfitController extends BaseController
{
    public function store()
    {
        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => 'Invalid JSON']);
        }

        if (
            !isset($data['title']) ||
            !isset($data['image_url'])
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'error' => 'title and image_url are required'
                ]);
        }

        $model = new OutfitModel();

        $model->insert([
            'title' => $data['title'],
            'image_url' => $data['image_url'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'message' => 'Outfit saved successfully'
            ]);
    }
}
