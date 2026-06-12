<?php

namespace App\Observers;

use App\Models\Campaign;
use App\Models\User;
use App\Mail\UrgentCampaignMail;
use Illuminate\Support\Facades\Mail;

class CampaignObserver
{
    public function updated(Campaign $campaign)
    {
        // Si la campagne vient d'être passée en urgente
        if ($campaign->wasChanged('is_urgent') && $campaign->is_urgent) {
            $donors = User::whereHas('donations')->get();
            foreach ($donors as $donor) {
                Mail::to($donor->email)->queue(new UrgentCampaignMail($campaign));
            }
        }
    }
}