<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'website_id',
        'domain',
        'status',
        'regperiod',
        'registered_at',
        'expires_at',
        'auto_renew',
        'id_protection',
        'registrar_locked',
        'nameservers',
        'contacts',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
            'auto_renew' => 'boolean',
            'id_protection' => 'boolean',
            'registrar_locked' => 'boolean',
            'nameservers' => 'array',
            'contacts' => 'array',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(DomainOrder::class)->latest();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
