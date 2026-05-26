<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    // Ceci permet d'autoriser l'ajout de données dans ces colonnes
    protected $fillable = [
        'title', 
        'description', 
        'goal_amount', 
        'collected_amount', 
        'image', 
        'category_id'
    ];

    // Ceci définit la relation avec la catégorie
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}