<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainMetric extends Model
{
    protected $fillable = [
        'domain_id',
        'da',
        'pa',
        'alexa_rank',
        'semrush_rank',
        'backlinks_count',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
