<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'keywords_count' => $this->keywords_count ?? $this->keywords()->count(),
            'domains_count' => $this->domains_count ?? $this->domains()->count(),
            'contents_count' => $this->contents_count ?? $this->contents()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
