<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }
   public function show($id)
{
    $user = $this->userModel->find($id);

    if (!$user) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ])->setStatusCode(404);
    }

    return $this->response->setJSON([
        'success' => true,
        'user' => [
            'user_id' => (string) $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            // ⬇️ FOTO DITAMPILKAN (URL)
            'photo' => $user['photo']
                ? base_url('uploads/profiles/' . $user['photo'])
                : null,
            'style_preference' => $user['style_preference'],
            'created_at' => $user['created_at'],
        
        ]
    ]);
}

    public function update($id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ])->setStatusCode(404);
        }

        $name  = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $style = $this->request->getPost('style_preference');
        $file  = $this->request->getFile('photo');

        if (!$name || !$email) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nama dan email wajib diisi'
            ])->setStatusCode(400);
        }

        $dataUpdate = [
            'name'  => $name,
            'email' => $email
        ];

        if ($style) {
            $dataUpdate['style_preference'] = $style;
        }

        if ($file && $file->isValid() && !$file->hasMoved()) {

            $allowedMime = ['image/jpg', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMime)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Format foto harus JPG atau PNG'
                ])->setStatusCode(400);
            }

            $newName = $file->getRandomName();
            $file->move('uploads/profiles', $newName);
            if ($user['photo'] && file_exists('uploads/profiles/' . $user['photo'])) {
                unlink('uploads/profiles/' . $user['photo']);
            }

            $dataUpdate['photo'] = $newName;
        }

        $this->userModel->update($id, $dataUpdate);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Profile berhasil diperbarui'
        ]);
    }

    public function deletePhoto($id)
    {
        $user = $this->userModel->find($id);

        if (!$user || !$user['photo']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Foto tidak ditemukan'
            ])->setStatusCode(404);
        }

        if (file_exists('uploads/profiles/' . $user['photo'])) {
            unlink('uploads/profiles/' . $user['photo']);
        }

        $this->userModel->update($id, ['photo' => null]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Foto profile berhasil dihapus'
        ]);
    }
}
