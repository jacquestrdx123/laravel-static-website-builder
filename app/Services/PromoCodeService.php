<?php

namespace App\Services;

use App\Exceptions\PromoCodeException;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    public function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function findValid(string $code): PromoCode
    {
        $normalized = $this->normalize($code);

        if ($normalized === '') {
            throw new PromoCodeException('Invalid code.');
        }

        $promo = PromoCode::query()->where('code', $normalized)->first();

        if ($promo === null) {
            throw new PromoCodeException('Invalid code.');
        }

        if (! $promo->is_active) {
            throw new PromoCodeException('This promo code is inactive.');
        }

        if ($promo->isExpired()) {
            throw new PromoCodeException('This promo code has expired.');
        }

        if ($promo->hasReachedMaxRedemptions()) {
            throw new PromoCodeException('This promo code has reached its redemption limit.');
        }

        return $promo;
    }

    public function assertUserCanRedeem(PromoCode $promo, User $user): void
    {
        $alreadyRedeemed = $promo->redemptions()
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyRedeemed) {
            throw new PromoCodeException('You have already used this promo code.');
        }
    }

    public function redeemCredits(PromoCode $promo, User $user): void
    {
        if ($promo->type !== PromoCode::TYPE_CREDITS) {
            throw new PromoCodeException('This promo code cannot be redeemed for credits.');
        }

        $credits = (int) $promo->credits_amount;

        if ($credits < 1) {
            throw new PromoCodeException('Invalid code.');
        }

        $this->assertUserCanRedeem($promo, $user);

        DB::transaction(function () use ($promo, $user, $credits) {
            $locked = PromoCode::query()->whereKey($promo->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_active || $locked->isExpired() || $locked->hasReachedMaxRedemptions()) {
                throw new PromoCodeException('This promo code is no longer available.');
            }

            $this->assertUserCanRedeem($locked, $user);

            $user->addCredits($credits, 'Promo code '.$locked->code);

            $locked->redemptions()->create([
                'user_id' => $user->id,
                'credits_granted' => $credits,
            ]);

            $locked->increment('times_redeemed');
        });
    }

    public function previewDiscount(PromoCode $promo, int $baseZar): int
    {
        if ($promo->type !== PromoCode::TYPE_CHECKOUT_DISCOUNT) {
            throw new PromoCodeException('This promo code is not a checkout discount.');
        }

        $value = (float) $promo->discount_value;

        if ($promo->discount_type === PromoCode::DISCOUNT_PERCENT) {
            $paid = (int) round($baseZar * (1 - ($value / 100)));
        } elseif ($promo->discount_type === PromoCode::DISCOUNT_FIXED) {
            $paid = (int) round($baseZar - $value);
        } else {
            throw new PromoCodeException('Invalid code.');
        }

        return max(0, $paid);
    }

    public function redeemCheckoutDiscount(
        PromoCode $promo,
        User $user,
        int $packCredits,
        int $baseZar,
        int $paidZar,
    ): void {
        if ($promo->type !== PromoCode::TYPE_CHECKOUT_DISCOUNT) {
            throw new PromoCodeException('This promo code is not a checkout discount.');
        }

        $this->assertUserCanRedeem($promo, $user);

        DB::transaction(function () use ($promo, $user, $packCredits, $baseZar, $paidZar) {
            $locked = PromoCode::query()->whereKey($promo->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_active || $locked->isExpired() || $locked->hasReachedMaxRedemptions()) {
                throw new PromoCodeException('This promo code is no longer available.');
            }

            $this->assertUserCanRedeem($locked, $user);

            $discountApplied = max(0, $baseZar - $paidZar);

            $locked->redemptions()->create([
                'user_id' => $user->id,
                'discount_applied_zar' => $discountApplied,
                'pack_credits' => $packCredits,
            ]);

            $locked->increment('times_redeemed');
        });
    }
}
