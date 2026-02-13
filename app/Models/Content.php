<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    protected $fillable = [
        'project_id',
        'domain_id',
        'title',
        'url',
        'snippet',
        'is_unique',
        'unique_checked_at',
        'status',
    ];

    protected $casts = [
        'is_unique' => 'boolean',
        'unique_checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ContentPlan::class);
    }

    public function markAsChecked(bool $isUnique): void
    {
        $this->update([
            'is_unique' => $isUnique,
            'status' => $isUnique ? 'unique' : 'duplicate',
            'unique_checked_at' => now(),
        ]);
    }
}
