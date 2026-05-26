<?php

namespace Database\Seeders;


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category; // <--- AJOUTE CETTE LIGNE

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create(['name' => 'Education']);
        Category::create(['name' => 'Santé']);
        Category::create(['name' => 'Urgence']);
    }
}