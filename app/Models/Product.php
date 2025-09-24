<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 
        'description', 
        'price', 
        'file_path', 
        'mime', 
        'file_size', 
        'is_active'
    ];
}
