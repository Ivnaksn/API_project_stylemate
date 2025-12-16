<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\WardrobeModel;

class WardrobeController extends ResourceController
{
    use ResponseTrait;
    private function getUserId()
    {
        $rawUser = $this->request->user ?? null;
        $userData = (array) $rawUser; 
        return $userData['user_id'] ?? $userData['id'] ?? $userData['uid'] ?? null;
    }
    public function index()
    {
        $model = new WardrobeModel();
        $userId = $this->getUserId();

        if (!$userId) return $this->failUnauthorized('Akses ditolak.');
        $data = $model->select('wardrobe_items.*, categories.name as category_name') 
                      ->join('categories', 'categories.category_id = wardrobe_items.category_id') 
                      ->where('wardrobe_items.user_id', $userId)
                      ->findAll();

        return $this->respond($data);
    }

    public function show($id = null)
    {
        $model = new WardrobeModel();
        $userId = $this->getUserId();
        $data = $model->select('wardrobe_items.*, categories.name as category_name')
                      ->join('categories', 'categories.category_id = wardrobe_items.category_id')
                      ->where('wardrobe_items.user_id', $userId)
                      ->find($id);

        if ($data) {
            return $this->respond($data);
        } else {
            return $this->failNotFound('Item tidak ditemukan.');
        }
    }

    public function delete($id = null)
    {
        $model = new WardrobeModel();
        $userId = $this->getUserId();
        $exist = $model->where('user_id', $userId)->find($id);
        
        if ($exist) {
            $model->delete($id);
            return $this->respondDeleted(['id' => $id, 'message' => 'Item berhasil dihapus']);
        } else {
            return $this->failNotFound('Item tidak ditemukan atau bukan milik Anda.');
        }
    }
}