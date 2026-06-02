<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateRequest;
use App\Http\Requests\UpdateUpdateRequest;
use App\Http\Resources\UpdateResource;
use App\Models\Campaign;
use App\Models\Update;

class UpdateController extends Controller
{
    public function index(Campaign $campaign)
    {
        return UpdateResource::collection($campaign->updates()->latest()->get());
    }

    public function show(Campaign $campaign, Update $update)
    {
        if ($update->campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Update not found for this campaign'], 404);
        }

        return new UpdateResource($update);
    }

    public function store(StoreUpdateRequest $request, Campaign $campaign)
    {
        $update = Update::create(array_merge($request->validated(), [
            'campaign_id' => $campaign->id,
        ]));

        return new UpdateResource($update);
    }

    public function update(UpdateUpdateRequest $request, Campaign $campaign, Update $update)
    {
        if ($update->campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Update not found for this campaign'], 404);
        }

        $update->update($request->validated());

        return new UpdateResource($update);
    }

    public function destroy(Campaign $campaign, Update $update)
    {
        if ($update->campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Update not found for this campaign'], 404);
        }

        $update->delete();

        return response()->json(['message' => 'Update deleted successfully']);
    }
}
