<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Campaign::create([
    'title' => 'Aide alimentaire',
    'description' => 'Aider les familles',
    'goal_amount' => 5000,
    'collected_amount' => 2500,
    'category_id' => 1
]);


   
    }
}
