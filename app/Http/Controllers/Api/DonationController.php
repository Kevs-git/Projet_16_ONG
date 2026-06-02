<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDonationRequest;
use App\Http\Resources\DonationResource;
use App\Mail\DonationThankYouMail;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Exception;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class DonationController extends Controller
{
    public function store(StoreDonationRequest $request)
    {
        $data = $request->validated();

        $donor = Donor::firstOrCreate(
            ['email' => $data['donor_email']],
            ['name' => $data['donor_name']]
        );

        $stripeSecret = config('services.stripe.secret');

        if (! $stripeSecret) {
            return response()->json([
                'message' => 'Stripe API key is not configured.',
                'hint' => 'Set STRIPE_SECRET in your .env file and restart the server.',
            ], 500);
        }

        if ($stripeSecret === 'sk_test_xxx' || strpos($stripeSecret, 'sk_test_') !== 0 && strpos($stripeSecret, 'sk_live_') !== 0) {
            return response()->json([
                'message' => 'Stripe API key is invalid or placeholder.',
                'hint' => 'Use a real Stripe test secret key in STRIPE_SECRET, not sk_test_xxx.',
            ], 500);
        }

        Stripe::setApiKey($stripeSecret);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($data['amount'] * 100),
                'currency' => 'usd',
                'payment_method' => $data['payment_method'],
                'payment_method_types' => ['card'],
                'confirm' => true,
                'receipt_email' => $donor->email,
                'metadata' => [
                    'campaign_id' => $data['campaign_id'],
                    'donor_id' => $donor->id,
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $exception->getMessage(),
            ], 402);
        }

        if ($paymentIntent->status !== 'succeeded') {
            return response()->json([
                'message' => 'Payment not completed',
                'status' => $paymentIntent->status,
            ], 402);
        }

        $campaign = Campaign::findOrFail($data['campaign_id']);

        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'donor_id' => $donor->id,
            'amount' => $data['amount'],
            'message' => $data['message'] ?? null,
            'payment_status' => 'succeeded',
            'stripe_payment_id' => $paymentIntent->id,
        ]);

        $campaign->increment('collected_amount', $data['amount']);

        Mail::to($donor->email)->send(new DonationThankYouMail($donation));

        return new DonationResource($donation->load('donor'));
    }
}
