<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'goal_amount',
        'collected_amount',
        'image',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function updates()
    {
        return $this->hasMany(Update::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function donors()
    {
        return $this->hasManyThrough(Donor::class, Donation::class, 'campaign_id', 'id', 'id', 'donor_id');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->goal_amount <= 0) {
            return 0.0;
        }

        return round(($this->collected_amount / $this->goal_amount) * 100, 2);
    }

    public function getUniqueDonorCountAttribute(): int
    {
        return $this->donations()->distinct('donor_id')->count('donor_id');
    }
}