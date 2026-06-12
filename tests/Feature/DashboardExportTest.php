<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;

test('admin financial report export requires authentication', function () {
    $response = $this->getJson('/api/admin/financial-report');
    $response->assertStatus(401);
});

test('authenticated admin can download financial report', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/financial-report');
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('dashboard route redirects unauthenticated users to login', function () {
    $response = $this->get('/dashboard');
    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

test('receipt route redirects unauthenticated users to login', function () {
    $category = Category::create(['name' => 'Private receipt']);
    $campaign = Campaign::create([
        'title' => 'Receipt campaign',
        'description' => 'Receipt protected',
        'goal_amount' => 100,
        'collected_amount' => 0,
        'category_id' => $category->id,
    ]);
    $donor = Donor::create(['name' => 'Authless Donor', 'email' => 'authless@example.com']);
    $donation = Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 25,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_authless',
        'receipt_number' => 'REC-AUTH-1',
    ]);

    $response = $this->get(route('donations.receipt', $donation));
    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

test('dashboard displays top donor names', function () {
    $category = Category::create(['name' => 'Top donors']);
    $campaign = Campaign::create([
        'title' => 'Leader campaign',
        'description' => 'Top donor test',
        'goal_amount' => 100,
        'collected_amount' => 10,
        'category_id' => $category->id,
    ]);
    $donor = Donor::create(['name' => 'Winner', 'email' => 'winner@example.com']);
    Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 10,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_winner',
        'receipt_number' => 'REC-WIN-1',
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Winner');
});

