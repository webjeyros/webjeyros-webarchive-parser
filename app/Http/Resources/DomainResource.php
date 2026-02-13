<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'available' => $this->available,
            'http_status' => $this->http_status,
            'title' => $this->title,
            'snippet' => $this->snippet,
            'archived_url' => $this->archived_url,
            'checked_at' => $this->checked_at,
            'metric' => new DomainMetricResource($this->whenLoaded('metric')),
            'keyword_id' => $this->keyword_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
