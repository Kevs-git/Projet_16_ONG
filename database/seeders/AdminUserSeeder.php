<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');

        User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => bcrypt($password), 'role' => 'admin']
        );
    }
}
