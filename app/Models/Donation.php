<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'donor_id',
        'amount',
        'status',
        'stripe_id',
        'is_recurring',
        'message',
        'payment_status',
        'stripe_payment_id',
        'receipt_number',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function donor()
    {
        return $this->belongsTo(Donor::class);
    }
}
