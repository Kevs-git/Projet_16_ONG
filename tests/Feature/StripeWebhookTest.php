<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Carbon\Carbon;

function stripeSignatureHeader(string $payload, string $secret): string
{
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    return "t={$timestamp},v1={$signature}";
}

test('webhook rejects invalid signature', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);
    $payload = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_bad', 'metadata' => []]]]);

    $response = $this->call('POST', '/api/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=123,v1=bad', 'CONTENT_TYPE' => 'application/json'], $payload);
    $response->assertStatus(400);
});

test('payment intent webhook updates an existing donation status', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test', 'services.stripe.secret' => 'sk_test_123']);
    $category = App\Models\Category::create(['name' => 'Webhook payment']);
    $campaign = Campaign::create([
        'title' => 'Stripe campaign',
        'description' => 'Webhook payment test',
        'goal_amount' => 100,
        'collected_amount' => 0,
        'category_id' => $category->id,
    ]);
    $donor = Donor::create(['name' => 'Webhook Donor', 'email' => 'webhook@example.com']);
    $donation = Donation::create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 20,
        'payment_status' => 'pending',
        'stripe_payment_id' => 'pi_webhook',
        'receipt_number' => 'REC-WEBHOOK-1',
    ]);

    $payload = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_webhook']]]);
    $signature = stripeSignatureHeader($payload, config('services.stripe.webhook_secret'));

    $response = $this->call('POST', '/api/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload);
    $response->assertOk();
    $this->assertDatabaseHas('donations', ['id' => $donation->id, 'payment_status' => 'succeeded']);
});

test('invoice.payment_succeeded webhook creates a donation from metadata', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test', 'services.stripe.secret' => 'sk_test_123']);
    $category = Category::create(['name' => 'Webhook invoice']);
    $campaign = Campaign::create([
        'title' => 'Webhook campaign',
        'description' => 'Test',
        'goal_amount' => 100,
        'collected_amount' => 0,
        'category_id' => $category->id,
    ]);

    $payload = json_encode([
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'payment_intent' => 'pi_invoice_123',
                'amount_paid' => 5000,
                'customer_email' => 'invoice@example.com',
                'metadata' => ['campaign_id' => $campaign->id, 'donor_name' => 'Invoice Donor'],
                'lines' => ['data' => [['period' => ['end' => time() + 3600]]]],
            ],
        ],
    ]);
    $signature = stripeSignatureHeader($payload, config('services.stripe.webhook_secret'));

    $response = $this->call('POST', '/api/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload);
    $response->assertOk();
    $this->assertDatabaseHas('donations', ['stripe_payment_id' => 'pi_invoice_123', 'payment_status' => 'succeeded']);
});

test('customer.subscription.created webhook creates a subscription record', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test', 'services.stripe.secret' => 'sk_test_123']);
    $payload = json_encode([
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_123',
                'status' => 'active',
                'plan' => ['amount' => 2500, 'interval' => 'month'],
                'current_period_end' => time() + 86400,
                'metadata' => ['campaign_id' => null, 'donor_email' => 'sub@example.com', 'donor_name' => 'Sub donor'],
            ],
        ],
    ]);
    $signature = stripeSignatureHeader($payload, config('services.stripe.webhook_secret'));

    $response = $this->call('POST', '/api/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload);
    $response->assertOk();
    $this->assertDatabaseHas('subscriptions', ['stripe_subscription_id' => 'sub_123', 'status' => 'active']);
});

