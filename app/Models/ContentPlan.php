<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPlan extends Model
{
    protected $fillable = [
        'project_id',
        'content_id',
        'user_id',
        'status',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsTaken(): void
    {
        $this->update([
            'status' => 'taken',
            'taken_at' => now(),
        ]);
    }
}
