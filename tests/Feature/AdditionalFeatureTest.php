<?php

use App\Exports\FinancialReportExport;
use App\Mail\UrgentCampaignMail;
use App\Http\Resources\DonationResource;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);
});

test('campaign resource includes category details', function () {
    $category = Category::create(['name' => 'Resource category']);
    $campaign = Campaign::create([
        'title' => 'Resource campaign',
        'description' => 'Details',
        'goal_amount' => 200,
        'collected_amount' => 25,
        'category_id' => $category->id,
    ]);

    $response = $this->getJson('/api/campaigns/'.$campaign->id);
    $response->assertOk();
    $response->assertJsonPath('data.category.name', 'Resource category');
});

test('donation resource includes donor information', function () {
    $donor = Donor::create(['name' => 'Resource Donor', 'email' => 'resource@example.com']);
    $category = Category::create(['name' => 'Donation category']);
    $campaign = Campaign::create([
        'title' => 'Donation campaign',
        'description' => 'Donation detail',
        'goal_amount' => 500,
        'collected_amount' => 100,
        'category_id' => $category->id,
    ]);
    $donation = Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 40,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_resource',
        'receipt_number' => 'REC-RES-1',
    ]);

    $result = DonationResource::make($donation)->resolve();
    expect($result['donor']['name'])->toBe('Resource Donor');
    expect($result['donor']['email'])->toBe('resource@example.com');
});

test('urgent campaign mail subject includes campaign title', function () {
    Mail::fake();

    $category = Category::create(['name' => 'Mail subject']);
    $campaign = Campaign::create([
        'title' => 'Urgent subject',
        'description' => 'Mail test',
        'goal_amount' => 100,
        'collected_amount' => 0,
        'category_id' => $category->id,
        'is_urgent' => true,
    ]);

    $donor = Donor::create(['name' => 'Subject Donor', 'email' => 'subject@example.com']);
    Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 10,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_subject',
        'receipt_number' => 'REC-SUBJ-1',
    ]);

    // Construire le mailable et vérifier son sujet (test unitaire stable)
    $mail = new UrgentCampaignMail($campaign);
    expect($mail->build()->subject)->toBe('Campagne urgente : '.$campaign->title);
});

test('financial report export accepts a date range without error', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/financial-report?from=2026-01-01&to=2026-12-31');
    $response->assertStatus(200);
});
