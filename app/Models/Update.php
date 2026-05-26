<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Update extends Model
{
    protected $fillable = ['campaign_id', 'content'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}

