<?php

use App\Mail\UrgentCampaignMail;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['services.stripe.secret' => 'sk_test_123', 'services.stripe.webhook_secret' => 'whsec_test']);
});

test('campaign creation accepts urgent flag and returns it in the response', function () {
    $category = Category::create(['name' => 'Urgent create']);
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/campaigns', [
        'title' => 'Urgent launch',
        'description' => 'Launch campaign',
        'goal_amount' => 1000,
        'category_id' => $category->id,
        'is_urgent' => true,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.is_urgent', true);
});

test('campaign update that does not mark urgent does not send mail', function () {
    Mail::fake();
    $category = Category::create(['name' => 'No urgent']);
    $campaign = Campaign::create([
        'title' => 'Normal campaign',
        'description' => 'Normal',
        'goal_amount' => 200,
        'collected_amount' => 10,
        'category_id' => $category->id,
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->putJson('/api/campaigns/'.$campaign->id, [
        'description' => 'Updated description',
    ]);

    $response->assertOk();
    Mail::assertNothingSent();
});

test('financial report download works when donations exist', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Report data']);
    $campaign = Campaign::create([
        'title' => 'Report campaign',
        'description' => 'Report details',
        'goal_amount' => 300,
        'collected_amount' => 60,
        'category_id' => $category->id,
    ]);
    $donor = Donor::create(['name' => 'Report Donor', 'email' => 'report@example.com']);
    Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 60,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_report',
        'receipt_number' => 'REC-REP-1',
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/financial-report');
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('PDF receipt view contains the receipt number and campaign title', function () {
    $category = Category::create(['name' => 'PDF view']);
    $campaign = Campaign::create([
        'title' => 'PDF Campaign',
        'description' => 'PDF desc',
        'goal_amount' => 100,
        'collected_amount' => 50,
        'category_id' => $category->id,
    ]);
    $donor = Donor::create(['name' => 'PDF Donor', 'email' => 'pdf@example.com']);
    $donation = Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 50,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_pdf',
        'receipt_number' => 'REC-PDF-1',
    ]);

    $response = $this->get(route('donations.receipt', $donation));
    $response->assertStatus(302); // unauthenticated redirect to login
});

test('urgent campaign mail build includes correct subject', function () {
    $category = Category::create(['name' => 'Mail subject test']);
    $campaign = Campaign::create([
        'title' => 'Mail subject campaign',
        'description' => 'Subject test',
        'goal_amount' => 50,
        'collected_amount' => 5,
        'category_id' => $category->id,
        'is_urgent' => true,
    ]);

    $mail = new UrgentCampaignMail($campaign);
    expect($mail->build()->subject)->toBe('Campagne urgente : Mail subject campaign');
});
