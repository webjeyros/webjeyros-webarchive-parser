<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Domain extends Model
{
    protected $fillable = [
        'project_id',
        'keyword_id',
        'domain',
        'status',
        'available',
        'http_status',
        'title',
        'snippet',
        'archived_url',
        'checked_at',
        'first_captured',
        'last_captured',
        'capture_count',
        'webpage_count',
        'image_count',
        'video_count',
        'audio_count',
    ];

    protected $casts = [
        'available' => 'boolean',
        'checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'capture_count' => 'integer',
        'webpage_count' => 'integer',
        'image_count' => 'integer',
        'video_count' => 'integer',
        'audio_count' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function metric(): HasOne
    {
        return $this->hasOne(DomainMetric::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn($value) => match($this->status) {
                'new' => 'Новый',
                'checking' => 'Проверяется',
                'available' => 'Свободен',
                'occupied' => 'Занят',
                'dead' => 'Мертв',
                'in_work' => 'В работе',
                default => 'Неизвестно',
            }
        );
    }

    public function markAsAvailable(): void
    {
        $this->update([
            'status' => 'available',
            'available' => true,
            'checked_at' => now(),
        ]);
    }

    public function markAsOccupied(): void
    {
        $this->update([
            'status' => 'occupied',
            'available' => false,
            'checked_at' => now(),
        ]);
    }

    public function markAsDead(): void
    {
        $this->update([
            'status' => 'dead',
            'http_status' => 0,
            'checked_at' => now(),
        ]);
    }

    public function markInWork(): void
    {
        $this->update(['status' => 'in_work']);
    }
}
