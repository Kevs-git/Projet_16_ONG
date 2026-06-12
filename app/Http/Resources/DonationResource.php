<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'campaign_id' => $this->campaign_id,
            'donor' => [
                'id' => $this->donor?->id,
                'name' => $this->donor?->name,
                'email' => $this->donor?->email,
            ],
            'amount' => (int) $this->amount,
            'status' => $this->status ?? $this->payment_status,
            'stripe_id' => $this->stripe_id ?? $this->stripe_payment_id,
            'is_recurring' => (bool) $this->is_recurring,
            'receipt_number' => $this->receipt_number,
            'receipt_url' => $this->receipt_number ? route('donations.receipt', $this->id) : null,
            'message' => $this->message,
            'payment_status' => $this->payment_status,
            'stripe_payment_id' => $this->stripe_payment_id,
            'created_at' => $this->created_at,
        ];
    }
}
