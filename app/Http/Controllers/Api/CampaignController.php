<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Affiche la liste de toutes les campagnes.
     */
    public function index()
    {
        return CampaignResource::collection(Campaign::with('category')->get());
    }

    /**
     * Affiche une campagne spécifique.
     */
    public function show(Campaign $campaign)
    {
        return new CampaignResource($campaign->load('category'));
    }

    /**
     * Crée une nouvelle campagne.
     */
    public function store(StoreCampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());

        return new CampaignResource($campaign->load('category'));
    }

    /**
     * Met à jour une campagne existante.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $campaign->update($request->validated());

        return new CampaignResource($campaign->load('category'));
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