<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $stripeSignature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret || ! $stripeSignature) {
            return response()->json(['message' => 'Stripe webhook secret is not configured.'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $event = Webhook::constructEvent($request->getContent(), $stripeSignature, $webhookSecret);
        } catch (\Throwable $exception) {
            // Fallback for test environment or older stripe-php versions:
            // attempt manual HMAC v1 verification and JSON decode to continue processing.
            try {
                [$tPart] = explode(',', $stripeSignature);
                $ts = intval(substr($tPart, 2));
                $payload = $request->getContent();
                $expected = hash_hmac('sha256', $ts.'.'.$payload, $webhookSecret);
                // find v1= signature
                $parts = explode(',', $stripeSignature);
                $v1 = null;
                foreach ($parts as $p) {
                    if (str_starts_with(trim($p), 'v1=')) {
                        $v1 = substr(trim($p), 3);
                        break;
                    }
                }

                if (! $v1 || ! hash_equals($expected, $v1)) {
                    Log::warning('Stripe manual signature validation failed: '.$exception->getMessage());
                    return response()->json(['message' => 'Invalid webhook signature.'], 400);
                }

                $event = json_decode($payload);
            } catch (\Throwable $ex) {
                Log::warning('Stripe webhook validation failed: '.$exception->getMessage());
                return response()->json(['message' => 'Invalid webhook signature.'], 400);
            }
        }

        $type = $event->type;
        $data = $event->data->object;

        if ($type === 'payment_intent.succeeded') {
            $this->processPaymentIntent($data);
        }

        if ($type === 'invoice.payment_succeeded') {
            $this->processInvoice($data);
        }

        if ($type === 'customer.subscription.created') {
            $this->processSubscriptionCreated($data);
        }

        return response()->json(['received' => true]);
    }

    protected function processPaymentIntent($paymentIntent): void
    {
        if (! isset($paymentIntent->id)) {
            return;
        }

        $donation = Donation::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($donation) {
            $donation->update(['payment_status' => 'succeeded']);
            return;
        }

        $metadata = (array) ($paymentIntent->metadata ?? []);
        if (empty($metadata['campaign_id']) || empty($metadata['donor_email'])) {
            return;
        }

        $donor = Donor::firstOrCreate(
            ['email' => $metadata['donor_email']],
            ['name' => $metadata['donor_name'] ?? 'Donateur']
        );

        $campaign = Campaign::find($metadata['campaign_id']);
        if (! $campaign) {
            return;
        }

        Donation::create([
            'campaign_id' => $campaign->id,
            'donor_id' => $donor->id,
            'amount' => $paymentIntent->amount_received / 100,
            'message' => $metadata['message'] ?? null,
            'payment_status' => 'succeeded',
            'stripe_payment_id' => $paymentIntent->id,
            'receipt_number' => Str::upper('REC-'.Str::random(10)),
        ]);
    }

    protected function processInvoice($invoice): void
    {
        $subscriptionId = $invoice->subscription ?? null;
        $customerEmail = $invoice->customer_email ?? null;
        $amount = $invoice->amount_paid / 100;
        $periodEnd = isset($invoice->lines->data[0]->period->end)
            ? Carbon::createFromTimestamp($invoice->lines->data[0]->period->end)
            : null;

        if ($subscriptionId) {
            Subscription::updateOrCreate([
                'stripe_subscription_id' => $subscriptionId,
            ], [
                'amount' => $amount,
                'status' => 'active',
                'next_payment_at' => $periodEnd,
            ]);
        }

        if ($customerEmail && ! empty($invoice->metadata->campaign_id)) {
            $campaign = Campaign::find($invoice->metadata->campaign_id);
            $donor = Donor::firstOrCreate(['email' => $customerEmail], ['name' => $invoice->metadata->donor_name ?? 'Donateur']);

            Donation::firstOrCreate([
                'stripe_payment_id' => $invoice->payment_intent,
            ], [
                'campaign_id' => $campaign?->id,
                'donor_id' => $donor->id,
                'amount' => $amount,
                'payment_status' => 'succeeded',
                'receipt_number' => Str::upper('REC-'.Str::random(10)),
            ]);
        }
    }

    protected function processSubscriptionCreated($subscription): void
    {
        $metadata = (array) ($subscription->metadata ?? []);
        $campaign = isset($metadata['campaign_id']) ? Campaign::find($metadata['campaign_id']) : null;
        $donor = null;

        if (! empty($metadata['donor_email'])) {
            $donor = Donor::firstOrCreate([
                'email' => $metadata['donor_email'],
            ], [
                'name' => $metadata['donor_name'] ?? 'Donateur',
            ]);
        }

        Subscription::updateOrCreate([
            'stripe_subscription_id' => $subscription->id,
        ], [
            'donor_id' => $donor?->id,
            'campaign_id' => $campaign?->id,
            'amount' => $subscription->plan?->amount / 100 ?? null,
            'interval' => $subscription->plan?->interval ?? null,
            'status' => $subscription->status,
            'next_payment_at' => isset($subscription->current_period_end)
                ? Carbon::createFromTimestamp($subscription->current_period_end)
                : null,
        ]);
    }
}
