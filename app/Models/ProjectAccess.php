<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAccess extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'can_edit',
        'granted_at',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
        'granted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
