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
        // WHOIS data
        'registrar',
        'created_date',
        'updated_date',
        'expiration_date',
        // HTTP and DNS
        'http_status_code',
        'ip_address',
        'last_http_check',
        'nameserver_1',
        'nameserver_2',
        'nameserver_3',
        // SEO metrics
        'backlink_count',
        'referring_domains',
        'domain_authority',
        'spam_score',
        'indexed_pages',
        'total_pages',
        'external_links',
        'internal_links',
        // Metadata
        'metrics_source',
        'metrics_checked_at',
        'metrics_available',
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
        'metrics_available' => 'boolean',
        'checked_at' => 'datetime',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'expiration_date' => 'datetime',
        'last_http_check' => 'datetime',
        'metrics_checked_at' => 'datetime',
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

    /**
     * Check if domain is available
     */
    public function isAvailable(): bool
    {
        return $this->available === true;
    }

    /**
     * Check if domain is occupied
     */
    public function isOccupied(): bool
    {
        return $this->available === false && $this->http_status_code > 0;
    }

    /**
     * Check if domain is dead (no response)
     */
    public function isDead(): bool
    {
        return $this->http_status_code === 0 || is_null($this->http_status_code);
    }

    /**
     * Get domain age in days
     */
    public function getDomainAgeInDays(): ?int
    {
        if ($this->created_date) {
            return $this->created_date->diffInDays(now());
        }
        return null;
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if ($this->expiration_date) {
            return now()->diffInDays($this->expiration_date);
        }
        return null;
    }

    /**
     * Check if domain is expiring soon (within 30 days)
     */
    public function isExpiringsoon(): bool
    {
        $daysLeft = $this->getDaysUntilExpiration();
        return $daysLeft !== null && $daysLeft <= 30 && $daysLeft > 0;
    }

    /**
     * Get SEO health score (0-100)
     */
    public function getSeoHealthScore(): float
    {
        $score = 0;

        // Domain age (0-20 points)
        if ($ageInDays = $this->getDomainAgeInDays()) {
            if ($ageInDays > 365) {
                $score += 20;
            } elseif ($ageInDays > 180) {
                $score += 15;
            } elseif ($ageInDays > 30) {
                $score += 10;
            }
        }

        // Domain Authority (0-30 points)
        if ($this->domain_authority) {
            $score += ($this->domain_authority / 100) * 30;
        }

        // Backlinks (0-25 points)
        if ($this->backlink_count) {
            $backlinks = min($this->backlink_count, 1000);
            $score += ($backlinks / 1000) * 25;
        }

        // Spam score (0-25 points)
        if ($this->spam_score !== null) {
            $score += (100 - $this->spam_score) / 100 * 25;
        }

        // HTTP status (0-10 points)
        if ($this->http_status_code === 200) {
            $score += 10;
        } elseif (in_array($this->http_status_code, [301, 302, 303, 307, 308])) {
            $score += 5;
        }

        return min(round($score, 2), 100);
    }

    /**
     * Mark as available
     */
    public function markAsAvailable(): void
    {
        $this->update([
            'status' => 'available',
            'available' => true,
            'checked_at' => now(),
        ]);
    }

    /**
     * Mark as occupied
     */
    public function markAsOccupied(): void
    {
        $this->update([
            'status' => 'occupied',
            'available' => false,
            'checked_at' => now(),
        ]);
    }

    /**
     * Mark as dead
     */
    public function markAsDead(): void
    {
        $this->update([
            'status' => 'dead',
            'http_status_code' => 0,
            'checked_at' => now(),
        ]);
    }

    /**
     * Mark in work
     */
    public function markInWork(): void
    {
        $this->update(['status' => 'in_work']);
    }
}
