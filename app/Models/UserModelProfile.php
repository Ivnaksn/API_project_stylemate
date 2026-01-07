<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModelProfile extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'name',
        'email',
        'photo',
        'password',
        'style_preference',
        'created_at'
    ];

    protected $useTimestamps = false;
}
