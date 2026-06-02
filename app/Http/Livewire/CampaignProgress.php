<?php

namespace App\Http\Livewire;

use App\Models\Campaign;
use Livewire\Component;

class CampaignProgress extends Component
{
    public int $campaignId;

    public function mount(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function render()
    {
        $campaign = Campaign::find($this->campaignId);

        return view('livewire.campaign-progress', [
            'campaign' => $campaign,
        ]);
    }
}
