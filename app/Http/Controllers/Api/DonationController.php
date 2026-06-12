<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DonationResource;
use App\Mail\DonationThankYouMail;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => DonationResource::collection(
                $request->user()->donations()->with(['campaign', 'donor'])->latest()->get()
            ),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has(['donor_name', 'donor_email', 'payment_method'])) {
            return $this->storeLegacyDonation($request);
        }

        $data = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'amount' => 'required|integer|min:1',
            'stripe_payment_id' => 'required|string|max:255',
            'is_recurring' => 'sometimes|boolean',
        ]);

        $user = $request->user() ?? auth('sanctum')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Verify PaymentIntent with Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::retrieve($data['stripe_payment_id']);

            // Verify the PaymentIntent status
            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'message' => 'Payment has not been confirmed. Status: '.$paymentIntent->status,
                ], 422);
            }

            // Verify amount matches
            if ($paymentIntent->amount !== $data['amount']) {
                return response()->json([
                    'message' => 'Amount mismatch. Expected: '.$data['amount'].', Got: '.$paymentIntent->amount,
                ], 422);
            }
        } catch (Exception $exception) {
            return response()->json([
                'message' => 'Failed to verify payment',
                'error' => $exception->getMessage(),
            ], 422);
        }

        $campaign = Campaign::findOrFail($data['campaign_id']);
        $donor = Donor::firstOrCreate(
            ['email' => $user->email],
            ['name' => $user->name]
        );
        $receiptNumber = Str::upper('REC-'.Str::random(8));

        // Handle recurring donations
        $isRecurring = (bool) ($data['is_recurring'] ?? false);

        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'donor_id' => $donor->id,
            'amount' => $data['amount'],
            'status' => 'succeeded',
            'stripe_id' => $paymentIntent->id,
            'is_recurring' => $isRecurring,
            'payment_status' => 'succeeded',
            'stripe_payment_id' => $data['stripe_payment_id'],
            'receipt_number' => $receiptNumber,
        ]);

        // Increment campaign totals
        $campaign->increment('montant_collecte', $data['amount']);
        $campaign->increment('collected_amount', $data['amount']);

        // Create recurring subscription if requested
        if ($isRecurring) {
            $this->createRecurringSubscription($donor, $campaign, $data['amount'], $paymentIntent->customer);
        }

        // Generate PDF receipt
        $donation->load(['campaign', 'donor']);
        $pdf = Pdf::loadView('pdf.donation_receipt', ['donation' => $donation]);
        Mail::to($donor->email)->send((new DonationThankYouMail($donation))
            ->attachData($pdf->output(), 'receipt-'.$receiptNumber.'.pdf', [
                'mime' => 'application/pdf',
            ]));

        return response()->json([
            'data' => [
                'donation_id' => $donation->id,
                'receipt_url' => route('donations.receipt', $donation->id),
                'is_recurring' => $isRecurring,
                'donation' => new DonationResource($donation->load('campaign')),
            ],
        ], 201);
    }

    /**
     * Create a recurring subscription on Stripe
     */
    private function createRecurringSubscription(Donor $donor, Campaign $campaign, int $amount, string $customerId): void
    {
        try {
            // Amount in cents - convert to monthly pricing
            $priceInCents = $amount * 100;

            // Create or get price for the subscription
            $priceId = config('services.stripe.monthly_price_id');

            if (!$priceId) {
                // Create a new price if not configured
                $plan = Plan::create([
                    'amount' => $priceInCents,
                    'currency' => config('services.stripe.currency', 'eur'),
                    'interval' => 'month',
                    'interval_count' => 1,
                    'product' => 'prod_recurring_donations',
                    'metadata' => [
                        'campaign_id' => $campaign->id,
                    ],
                ]);
                $priceId = $plan->id;
            }

            // Create Stripe subscription
            $subscription = StripeSubscription::create([
                'customer' => $customerId,
                'items' => [
                    [
                        'price' => $priceId,
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'donor_id' => $donor->id,
                ],
                'payment_behavior' => 'default_incomplete',
            ]);

            // Save subscription to database
            Subscription::create([
                'donor_id' => $donor->id,
                'campaign_id' => $campaign->id,
                'stripe_subscription_id' => $subscription->id,
                'amount' => $amount,
                'interval' => 'month',
                'status' => $subscription->status,
                'next_payment_at' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
            ]);
        } catch (Exception $exception) {
            // Log but don't fail - the one-time payment succeeded
            \Illuminate\Support\Facades\Log::error('Failed to create recurring subscription', [
                'donor_id' => $donor->id,
                'campaign_id' => $campaign->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function storeLegacyDonation(Request $request)
    {
        $data = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email|max:255',
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string',
            'payment_method' => 'required|string',
        ]);

        $donor = Donor::firstOrCreate(
            ['email' => $data['donor_email']],
            ['name' => $data['donor_name']]
        );

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($data['amount'] * 100),
                'currency' => config('services.stripe.currency', 'eur'),
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

        $campaign = Campaign::findOrFail($data['campaign_id']);
        $receiptNumber = Str::upper('REC-'.Str::random(8));

        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'donor_id' => $donor->id,
            'amount' => $data['amount'],
            'message' => $data['message'] ?? null,
            'status' => $paymentIntent->status,
            'stripe_id' => $paymentIntent->id,
            'payment_status' => $paymentIntent->status,
            'stripe_payment_id' => $paymentIntent->id,
            'receipt_number' => $receiptNumber,
        ]);

        if ($paymentIntent->status === 'succeeded') {
            $campaign->increment('montant_collecte', (int) $data['amount']);
            $campaign->increment('collected_amount', $data['amount']);
        }

        $donation->load(['campaign', 'donor']);
        $pdf = Pdf::loadView('pdf.donation_receipt', ['donation' => $donation]);
        Mail::to($donor->email)->send((new DonationThankYouMail($donation))
            ->attachData($pdf->output(), 'receipt-'.$receiptNumber.'.pdf', [
                'mime' => 'application/pdf',
            ]));

        return response()->json([
            'data' => new DonationResource($donation),
        ], 201);
    }
}

