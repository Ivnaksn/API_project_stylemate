<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\JWTLibrary;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    protected $modelName = 'App\Models\UserModel';
    protected $format = 'json';
    
    public function register()
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'style_preference' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }
        
        $data = [
            'name' => $this->request->getVar('name'),
            'email' => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'style_preference' => $this->request->getVar('style_preference')
        ];
        
        $userId = $this->model->insert($data);
        
        if ($userId) {
            return $this->respondCreated([
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $userId
            ]);
        }
        
        return $this->fail('Failed to register user');
    }
    
    public function login()
    {
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }
        
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        
        $user = $this->model->where('email', $email)->first();
        
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Invalid credentials');
        }
        
        // Generate JWT token
        $jwt = new JWTLibrary();
        $token = $jwt->generateToken([
            'user_id' => $user['user_id'],
            'email' => $user['email']
        ]);
        
        unset($user['password']);
        
        return $this->respond([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
    }
    
    public function logout()
    {
        return $this->respond([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    public function profile()
    {
        $userId = $this->request->user['user_id'];
        $user = $this->model->find($userId);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        unset($user['password']);
        
        return $this->respond([
            'success' => true,
            'user' => $user
        ]);
    }
}