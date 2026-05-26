<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // C'est ici que tu appelles tes classes de seeder
        $this->call([
            CategorySeeder::class,
            CampaignSeeder::class,
        ]);
    }
}