<?php

use App\Models\User;

it('registers and logs in an API user with valid credentials', function () {
    $registerResponse = $this->postJson('/api/register', [
        'name' => 'API Tester',
        'email' => 'api@example.com',
        'password' => 'password123',
    ]);

    $registerResponse->assertStatus(200)
        ->assertJsonPath('email', 'api@example.com');

    $loginResponse = $this->postJson('/api/login', [
        'email' => 'api@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(200)
        ->assertJsonPath('user.email', 'api@example.com')
        ->assertJsonStructure(['token', 'user']);

    expect(User::where('email', 'api@example.com')->exists())->toBeTrue();
});
