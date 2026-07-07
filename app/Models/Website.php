<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Website extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'user_id', 'name', 'slug', 'status', 'settings', 'error',
        'custom_domain', 'generated_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(WebsiteImage::class)->orderBy('sort');
    }

    /** Directory (on the local disk) holding the generated static site. */
    public function sitePath(): string
    {
        return Storage::disk('local')->path('sites/'.$this->slug);
    }

    public function isGenerated(): bool
    {
        return in_array($this->status, [self::STATUS_READY, self::STATUS_PUBLISHED], true);
    }

    public function isBusy(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_GENERATING], true);
    }

    /** Hostname the site is served from once published, e.g. my-shop.sites.example.com */
    public function hostname(): string
    {
        return $this->custom_domain ?: $this->slug.'.'.config('sites.domain');
    }
}
