<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected function status(): Attribute
    {
        return Attribute::make(
            default: 'idle',
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function contentPlans(): HasMany
    {
        return $this->hasMany(ContentPlan::class);
    }

    public function projectAccess(): HasMany
    {
        return $this->hasMany(ProjectAccess::class);
    }

    public function collaborators(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ProjectAccess::class, 'project_id', 'id', 'id', 'user_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->isOwnedBy($user) || $this->projectAccess()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canEditBy(User $user): bool
    {
        return $this->isOwnedBy($user) || $this->projectAccess()
            ->where('user_id', $user->id)
            ->where('can_edit', true)
            ->exists();
    }
}
