<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DomainMetricResource extends JsonResource
{
    public function toArray($request)
    {
        if ($this->resource === null) {
            return null;
        }

        return [
            'id' => $this->id,
            'da' => $this->da,
            'pa' => $this->pa,
            'alexa_rank' => $this->alexa_rank,
            'semrush_rank' => $this->semrush_rank,
            'backlinks_count' => $this->backlinks_count,
            'checked_at' => $this->checked_at,
        ];
    }
}
