<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UpdateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'title' => $this->title,
            'content' => $this->content,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
