<?php

namespace Tests\Feature;

use App\Livewire\Billing;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_credit_promo_grants_credits_and_blocks_second_redeem(): void
    {
        $user = User::factory()->create(['ai_credits' => 2]);
        PromoCode::query()->create([
            'code' => 'WELCOME15',
            'type' => PromoCode::TYPE_CREDITS,
            'credits_amount' => 15,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('creditPromoCode', 'welcome15')
            ->call('redeemPromoCode')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame(17, $user->ai_credits);
        $this->assertDatabaseHas('promo_code_redemptions', [
            'user_id' => $user->id,
            'credits_granted' => 15,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 15,
            'description' => 'Promo code WELCOME15',
        ]);
        $this->assertSame(1, PromoCode::query()->where('code', 'WELCOME15')->value('times_redeemed'));

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('creditPromoCode', 'WELCOME15')
            ->call('redeemPromoCode')
            ->assertHasErrors(['creditPromoCode']);

        $this->assertSame(17, $user->fresh()->ai_credits);
        $this->assertSame(1, PromoCodeRedemption::query()->count());
    }

    public function test_livewire_checkout_discount_applies_on_purchase(): void
    {
        $user = User::factory()->create(['ai_credits' => 0]);
        PromoCode::query()->create([
            'code' => 'SAVE20',
            'type' => PromoCode::TYPE_CHECKOUT_DISCOUNT,
            'discount_type' => PromoCode::DISCOUNT_PERCENT,
            'discount_value' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('checkoutPromoCode', 'SAVE20')
            ->call('applyCheckoutPromo')
            ->assertHasNoErrors()
            ->assertSet('appliedCheckoutPromo', 'SAVE20')
            ->assertSet('discountedPackPrices.5', 79)
            ->assertSet('discountedPackPrices.15', 199)
            ->assertSet('discountedPackPrices.50', 559)
            ->call('purchase', 5)
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame(5, $user->ai_credits);
        $this->assertDatabaseHas('promo_code_redemptions', [
            'user_id' => $user->id,
            'pack_credits' => 5,
            'discount_applied_zar' => 20,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 5,
            'description' => 'Credit pack purchase (R99 → R79, promo SAVE20) [stub - no payment taken]',
        ]);
    }

    public function test_http_purchase_with_fixed_discount_promo(): void
    {
        $user = User::factory()->create(['ai_credits' => 1]);
        PromoCode::query()->create([
            'code' => 'TENOFF',
            'type' => PromoCode::TYPE_CHECKOUT_DISCOUNT,
            'discount_type' => PromoCode::DISCOUNT_FIXED,
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('billing.purchase'), [
                'credits' => 15,
                'promo_code' => 'tenoff',
            ])
            ->assertRedirect(route('billing.index'));

        $this->assertSame(16, $user->fresh()->ai_credits);
        $this->assertDatabaseHas('promo_code_redemptions', [
            'user_id' => $user->id,
            'pack_credits' => 15,
            'discount_applied_zar' => 10,
        ]);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 15,
            'description' => 'Credit pack purchase (R249 → R239, promo TENOFF) [stub - no payment taken]',
        ]);
    }

    public function test_rejects_inactive_expired_exhausted_and_wrong_type_codes(): void
    {
        $user = User::factory()->create(['ai_credits' => 0]);

        PromoCode::query()->create([
            'code' => 'INACTIVE',
            'type' => PromoCode::TYPE_CREDITS,
            'credits_amount' => 5,
            'is_active' => false,
        ]);
        PromoCode::query()->create([
            'code' => 'EXPIRED',
            'type' => PromoCode::TYPE_CREDITS,
            'credits_amount' => 5,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);
        PromoCode::query()->create([
            'code' => 'MAXED',
            'type' => PromoCode::TYPE_CREDITS,
            'credits_amount' => 5,
            'is_active' => true,
            'max_redemptions' => 1,
            'times_redeemed' => 1,
        ]);
        PromoCode::query()->create([
            'code' => 'DISCONLY',
            'type' => PromoCode::TYPE_CHECKOUT_DISCOUNT,
            'discount_type' => PromoCode::DISCOUNT_PERCENT,
            'discount_value' => 10,
            'is_active' => true,
        ]);
        PromoCode::query()->create([
            'code' => 'CREDITONLY',
            'type' => PromoCode::TYPE_CREDITS,
            'credits_amount' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('creditPromoCode', 'INACTIVE')
            ->call('redeemPromoCode')
            ->assertHasErrors(['creditPromoCode']);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('creditPromoCode', 'EXPIRED')
            ->call('redeemPromoCode')
            ->assertHasErrors(['creditPromoCode']);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('creditPromoCode', 'MAXED')
            ->call('redeemPromoCode')
            ->assertHasErrors(['creditPromoCode']);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('creditPromoCode', 'DISCONLY')
            ->call('redeemPromoCode')
            ->assertHasErrors(['creditPromoCode']);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->set('checkoutPromoCode', 'CREDITONLY')
            ->call('applyCheckoutPromo')
            ->assertHasErrors(['checkoutPromoCode']);

        $this->assertSame(0, $user->fresh()->ai_credits);
        $this->assertSame(0, PromoCodeRedemption::query()->count());
    }

    public function test_pack_purchase_without_promo_still_works(): void
    {
        $user = User::factory()->create(['ai_credits' => 1]);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->call('purchase', 5)
            ->assertHasNoErrors();

        $this->assertSame(6, $user->fresh()->ai_credits);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 5,
            'description' => 'Credit pack purchase (R99) [stub - no payment taken]',
        ]);
        $this->assertSame(0, PromoCodeRedemption::query()->count());
    }
}
