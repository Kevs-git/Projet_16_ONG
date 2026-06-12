<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use App\Models\Donation;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Gérer le paiement réussi d'une facture (abonnement)
        if ($event->type === 'invoice.paid') {
            $invoice = $event->data->object;
            
            // On cherche l'abonnement correspondant en base
            $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

            if ($subscription) {
                // Créer une nouvelle entrée de donation pour ce mois
                Donation::create([
                    'user_id' => $subscription->user_id,
                    'campaign_id' => $subscription->campaign_id,
                    'amount' => $invoice->amount_paid,
                    'status' => 'succeeded',
                    'stripe_payment_id' => $invoice->payment_intent,
                    'is_recurring' => true,
                    'receipt_number' => 'REC-' . strtoupper(uniqid())
                ]);
                
                Log::info('Donation récurrente enregistrée via Webhook', ['sub' => $subscription->id]);
            }
        }

        return response()->json(['status' => 'success']);
    }
}