<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'donor' => [
                'id' => $this->donor?->id,
                'name' => $this->donor?->name,
                'email' => $this->donor?->email,
            ],
            'amount' => $this->amount,
            'message' => $this->message,
            'payment_status' => $this->payment_status,
            'stripe_payment_id' => $this->stripe_payment_id,
            'created_at' => $this->created_at,
        ];
    }
}
