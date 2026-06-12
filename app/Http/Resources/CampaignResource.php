<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $categoryName = $this->getRawOriginal('category')
            ?? ($this->resource->relationLoaded('category') ? $this->resource->getRelation('category')?->name : null);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $categoryName,
            'image_url' => $this->image_url ?? $this->image,
            'montant_collecte' => (int) ($this->montant_collecte ?? $this->collected_amount ?? 0),
            'montant_objectif' => (int) ($this->montant_objectif ?? $this->goal_amount ?? 0),
            'progress_percentage' => $this->progress_percentage,
            'unique_donor_count' => $this->unique_donor_count,
            'is_urgent' => (bool) $this->is_urgent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
