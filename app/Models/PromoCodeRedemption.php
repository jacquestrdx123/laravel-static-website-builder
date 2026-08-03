<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeRedemption extends Model
{
    protected $fillable = [
        'promo_code_id',
        'user_id',
        'credits_granted',
        'discount_applied_zar',
        'pack_credits',
    ];

    protected function casts(): array
    {
        return [
            'credits_granted' => 'integer',
            'discount_applied_zar' => 'decimal:2',
            'pack_credits' => 'integer',
        ];
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
