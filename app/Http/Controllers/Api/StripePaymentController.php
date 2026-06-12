<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Exception;
use Illuminate\Http\Request;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'campaign_id' => 'required|exists:campaigns,id',
            'is_recurring' => 'sometimes|boolean',
        ]);

        $stripeSecret = config('services.stripe.secret');
        $publishableKey = config('services.stripe.public');

        if (! $stripeSecret || ! $publishableKey) {
            return response()->json([
                'message' => 'Stripe API keys are not configured.',
            ], 500);
        }

        Stripe::setApiKey($stripeSecret);

        $campaign = Campaign::findOrFail($validated['campaign_id']);
        $user = $request->user();

        try {
            // Create or retrieve Stripe Customer
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            // Create Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $validated['amount'],
                'currency' => config('services.stripe.currency', 'eur'),
                'customer' => $customer->id,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'message' => 'Stripe payment intent creation failed',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'publishableKey' => $publishableKey,
                'customerId' => $customer->id,
                'amount' => $validated['amount'],
                'currency' => config('services.stripe.currency', 'eur'),
                'campaign_id' => $campaign->id,
            ],
        ]);
    }
}
