<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keyword extends Model
{
    protected $fillable = [
        'project_id',
        'keyword',
        'status',
        'parsed_count',
        'last_parsed_at',
    ];

    protected $casts = [
        'last_parsed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function markAsParsed(): void
    {
        $this->update([
            'status' => 'parsed',
            'parsed_count' => $this->parsed_count + 1,
            'last_parsed_at' => now(),
        ]);
    }
}
