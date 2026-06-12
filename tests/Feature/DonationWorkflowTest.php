<?php

use App\Exports\FinancialReportExport;
use App\Mail\UrgentCampaignMail;
use App\Http\Resources\DonationResource;
use App\Mail\DonationThankYouMail;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Mockery;

beforeEach(function () {
    config(['services.stripe.secret' => 'sk_test_123456789']);
});

test('a donation can be created and returns a receipt url', function () {
    $category = Category::create(['name' => 'Test category']);
    $campaign = Campaign::create([
        'title' => 'Test campaign',
        'description' => 'Test description',
        'goal_amount' => 1000,
        'collected_amount' => 0,
        'category_id' => $category->id,
    ]);

    Mail::fake();
    $paymentIntent = (object) [
        'id' => 'pi_test_123',
        'status' => 'succeeded',
    ];

    Mockery::mock('alias:Stripe\\PaymentIntent')
        ->shouldReceive('create')
        ->once()
        ->andReturn($paymentIntent);

    $response = $this->postJson('/api/donations', [
        'campaign_id' => $campaign->id,
        'donor_name' => 'Jane Doe',
        'donor_email' => 'jane@example.com',
        'amount' => 50,
        'payment_method' => 'pm_test_123',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['data' => ['id', 'receipt_number', 'receipt_url', 'donor', 'amount']]);
    $this->assertDatabaseHas('donations', ['stripe_payment_id' => 'pi_test_123', 'payment_status' => 'succeeded']);

    Mail::assertSent(DonationThankYouMail::class, 1);
});

test('receipt download route returns a PDF for an authenticated user', function () {
    $user = User::factory()->create();
    $donor = Donor::create(['name' => 'Jean', 'email' => 'jean@example.com']);
    $category = Category::create(['name' => 'Category']);
    $campaign = Campaign::create([
        'title' => 'Campaign',
        'description' => 'Desc',
        'goal_amount' => 100,
        'collected_amount' => 100,
        'category_id' => $category->id,
    ]);
    $donation = Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 30,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_abc',
        'receipt_number' => 'REC-TEST-123',
    ]);

    $response = $this->actingAs($user)->get(route('donations.receipt', $donation));
    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
});

test('financial report export returns an Excel file for authenticated admin', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/financial-report');
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('urgent campaign update sends notification emails to donors', function () {
    Mail::fake();
    $category = Category::create(['name' => 'Urgent category']);
    $campaign = Campaign::create([
        'title' => 'Urgent campaign',
        'description' => 'A campaign',
        'goal_amount' => 500,
        'collected_amount' => 10,
        'category_id' => $category->id,
    ]);
    $donor = Donor::create(['name' => 'Pierre', 'email' => 'pierre@example.com']);
    Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 10,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_send',
        'receipt_number' => 'REC-URGENT-1',
    ]);

    $user = User::factory()->create();
    $response = $this->actingAs($user, 'sanctum')->putJson('/api/campaigns/'.$campaign->id, ['is_urgent' => true]);

    $response->assertOk();
    Mail::assertSent(UrgentCampaignMail::class, 1);
});

test('campaign resource exposes urgent status', function () {
    $category = Category::create(['name' => 'Urgency']);
    $campaign = Campaign::create([
        'title' => 'Demo',
        'description' => 'Demo',
        'goal_amount' => 150,
        'collected_amount' => 20,
        'is_urgent' => true,
        'category_id' => $category->id,
    ]);

    $response = $this->getJson('/api/campaigns/'.$campaign->id);
    $response->assertOk();
    $response->assertJsonPath('data.is_urgent', true);
});

test('dashboard shows campaign and donor totals to authenticated users', function () {
    $category = Category::create(['name' => 'Metrics']);
    Campaign::create([
        'title' => 'Stat campaign',
        'description' => 'Stats',
        'goal_amount' => 1000,
        'collected_amount' => 150,
        'category_id' => $category->id,
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Total campagnes');
});

test('dashboard page displays urgent campaign badge when campaign is marked urgent', function () {
    $category = Category::create(['name' => 'Urgent stats']);
    Campaign::create([
        'title' => 'Urgence',
        'description' => 'Alerte',
        'goal_amount' => 200,
        'collected_amount' => 50,
        'category_id' => $category->id,
        'is_urgent' => true,
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Urgente');
});

test('receipt_url is available in donation resource data', function () {
    $donor = Donor::create(['name' => 'Test Donor', 'email' => 'test@example.com']);
    $category = Category::create(['name' => 'Receipt']);
    $campaign = Campaign::create([
        'title' => 'Receipt campaign',
        'description' => 'Receipt',
        'goal_amount' => 120,
        'collected_amount' => 120,
        'category_id' => $category->id,
    ]);
    $donation = Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 20,
        'payment_status' => 'succeeded',
        'stripe_payment_id' => 'pi_receipt',
        'receipt_number' => 'REC-12345',
    ]);

    $result = DonationResource::make($donation)->resolve();

    expect($result['receipt_number'])->toBe('REC-12345');
    expect($result['receipt_url'])->toBe(route('donations.receipt', $donation->id));
});

test('financial report export class returns the expected headings', function () {
    $export = new FinancialReportExport();

    expect($export->headings())->toEqual([
        'Reçu',
        'Campagne',
        'Donateur',
        'Email',
        'Montant',
        'Statut de paiement',
        'Date',
    ]);
});
