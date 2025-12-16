<?php

namespace App\Models;

use CodeIgniter\Model;

class WardrobeModel extends Model
{
    protected $table            = 'wardrobe_items'; 
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true; 
    protected $allowedFields    = [
        'user_id', 
        'category_id', 
        'name', 
        'image_url', 
        'color', 
        'style'
    ];
}