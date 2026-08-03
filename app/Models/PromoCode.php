<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    public const TYPE_CREDITS = 'credits';

    public const TYPE_CHECKOUT_DISCOUNT = 'checkout_discount';

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'type',
        'credits_amount',
        'discount_type',
        'discount_value',
        'is_active',
        'expires_at',
        'max_redemptions',
        'times_redeemed',
    ];

    protected function casts(): array
    {
        return [
            'credits_amount' => 'integer',
            'discount_value' => 'decimal:2',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'max_redemptions' => 'integer',
            'times_redeemed' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PromoCode $promo): void {
            $promo->code = strtoupper(trim((string) $promo->code));

            if ($promo->type === self::TYPE_CREDITS) {
                $promo->discount_type = null;
                $promo->discount_value = null;
            }

            if ($promo->type === self::TYPE_CHECKOUT_DISCOUNT) {
                $promo->credits_amount = null;
            }
        });
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoCodeRedemption::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedMaxRedemptions(): bool
    {
        return $this->max_redemptions !== null && $this->times_redeemed >= $this->max_redemptions;
    }

    public function rewardSummary(): string
    {
        if ($this->type === self::TYPE_CREDITS) {
            return ($this->credits_amount ?? 0).' credits';
        }

        if ($this->discount_type === self::DISCOUNT_PERCENT) {
            return rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.').'% off';
        }

        return 'R'.rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.').' off';
    }
}
