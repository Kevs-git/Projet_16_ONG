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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'goal_amount' => $this->goal_amount,
            'collected_amount' => $this->collected_amount,
            'image' => $this->image,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'progress_percentage' => $this->progress_percentage,
            'unique_donor_count' => $this->unique_donor_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
