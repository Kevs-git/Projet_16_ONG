<?php

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UrgentCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public Campaign $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function build()
    {
        return $this->subject('Campagne urgente : '.$this->campaign->title)
            ->view('emails.urgent_campaign')
            ->with([
                'campaignTitle' => $this->campaign->title,
                'campaignDescription' => $this->campaign->description,
            ]);
    }
}
