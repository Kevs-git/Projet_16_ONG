<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UrgentCampaignMail;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Category;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CampaignController extends Controller
{
    /**
     * Affiche la liste de toutes les campagnes.
     */
    public function index()
    {
        return response()->json([
            'data' => CampaignResource::collection(Campaign::with('category')->get()),
        ]);
    }

    /**
     * Affiche une campagne spécifique.
     */
    public function show(Campaign $campaign)
    {
        return response()->json([
            'data' => new CampaignResource($campaign->load('category')),
        ]);
    }

    /**
     * Crée une nouvelle campagne.
     */
    public function store(StoreCampaignRequest $request)
    {
        $data = $this->normalizeCampaignPayload($request->validated());
        $campaign = Campaign::create($data);

        if ($campaign->is_urgent) {
            $this->notifyDonorsOfUrgentCampaign($campaign);
        }

        return response()->json([
            'data' => new CampaignResource($campaign->load('category')),
        ], 201);
    }

    /**
     * Met à jour une campagne existante.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $wasUrgent = $campaign->is_urgent;
        $campaign->update($this->normalizeCampaignPayload($request->validated(), false));

        if ($campaign->is_urgent && ! $wasUrgent) {
            $this->notifyDonorsOfUrgentCampaign($campaign);
        }

        return response()->json([
            'data' => new CampaignResource($campaign->load('category')),
        ]);
    }

    protected function notifyDonorsOfUrgentCampaign(Campaign $campaign): void
    {
        $donorEmails = $campaign->donations()
            ->with('donor')
            ->get()
            ->map(fn ($donation) => $donation->donor?->email)
            ->filter()
            ->unique()
            ->values()
            ->toArray();


        if (empty($donorEmails)) {
            return;
        }

        Mail::to($donorEmails)->send(new UrgentCampaignMail($campaign));
    }

    protected function normalizeCampaignPayload(array $data, bool $creating = true): array
    {
        if (array_key_exists('montant_objectif', $data)) {
            $data['goal_amount'] = $data['montant_objectif'];
        } elseif ($creating && ! array_key_exists('goal_amount', $data)) {
            $data['goal_amount'] = 0;
        }

        if (array_key_exists('montant_collecte', $data)) {
            $data['collected_amount'] = $data['montant_collecte'];
        } elseif ($creating && ! array_key_exists('collected_amount', $data)) {
            $data['collected_amount'] = 0;
        }

        if (array_key_exists('image_url', $data)) {
            $data['image'] = $data['image_url'];
        }

        if ($creating && ! array_key_exists('category_id', $data)) {
            $categoryName = $data['category'] ?? 'General';
            $data['category_id'] = Category::firstOrCreate(['name' => $categoryName])->id;
        }

        return $data;
    }

    /**
     * Supprime une campagne.
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully']);
    }
}
