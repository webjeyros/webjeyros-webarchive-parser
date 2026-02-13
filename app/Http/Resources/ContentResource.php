<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'snippet' => $this->snippet,
            'status' => $this->status,
            'is_unique' => $this->is_unique,
            'unique_checked_at' => $this->unique_checked_at,
            'domain' => new DomainResource($this->whenLoaded('domain')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
