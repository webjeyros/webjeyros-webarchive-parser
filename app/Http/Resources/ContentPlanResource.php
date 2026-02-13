<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentPlanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'content' => new ContentResource($this->whenLoaded('content')),
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'status' => $this->status,
            'taken_at' => $this->taken_at,
            'created_at' => $this->created_at,
        ];
    }
}
