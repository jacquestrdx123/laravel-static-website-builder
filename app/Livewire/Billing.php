<?php

namespace App\Livewire;

use App\Exceptions\PromoCodeException;
use App\Http\Controllers\BillingController;
use App\Models\PromoCode;
use App\Services\PromoCodeService;
use App\Support\CreditsPricing;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Billing')]
class Billing extends Component
{
    public string $creditPromoCode = '';

    public string $checkoutPromoCode = '';

    public ?string $appliedCheckoutPromo = null;

    /** @var array<int, int> */
    public array $discountedPackPrices = [];

    public function redeemPromoCode(PromoCodeService $promos): void
    {
        $this->resetErrorBag('creditPromoCode');

        try {
            $promo = $promos->findValid($this->creditPromoCode);
            $promos->redeemCredits($promo, auth()->user());
        } catch (PromoCodeException $e) {
            $this->addError('creditPromoCode', $e->getMessage());

            return;
        }

        $this->creditPromoCode = '';
        session()->flash('status', 'Promo code redeemed. Credits have been added to your account.');
    }

    public function applyCheckoutPromo(PromoCodeService $promos): void
    {
        $this->resetErrorBag('checkoutPromoCode');
        $this->appliedCheckoutPromo = null;
        $this->discountedPackPrices = [];

        try {
            $promo = $promos->findValid($this->checkoutPromoCode);
            $promos->assertUserCanRedeem($promo, auth()->user());

            if ($promo->type !== PromoCode::TYPE_CHECKOUT_DISCOUNT) {
                throw new PromoCodeException('Enter a checkout discount code here, or use Redeem for credit codes.');
            }

            $prices = [];
            foreach (BillingController::PACKS as $credits => $pack) {
                $prices[$credits] = $promos->previewDiscount($promo, $pack['price_zar']);
            }

            $this->appliedCheckoutPromo = $promo->code;
            $this->discountedPackPrices = $prices;
            $this->checkoutPromoCode = $promo->code;
        } catch (PromoCodeException $e) {
            $this->addError('checkoutPromoCode', $e->getMessage());
        }
    }

    public function clearCheckoutPromo(): void
    {
        $this->checkoutPromoCode = '';
        $this->appliedCheckoutPromo = null;
        $this->discountedPackPrices = [];
        $this->resetErrorBag('checkoutPromoCode');
    }

    public function purchase(int $credits, PromoCodeService $promos): void
    {
        $packs = BillingController::PACKS;

        if (! array_key_exists($credits, $packs)) {
            $this->addError('credits', 'Invalid credit pack.');

            return;
        }

        $pack = $packs[$credits];
        $baseZar = $pack['price_zar'];
        $paidZar = $baseZar;
        $description = 'Credit pack purchase ('.$pack['label'].') [stub - no payment taken]';

        if ($this->appliedCheckoutPromo !== null) {
            try {
                $promo = $promos->findValid($this->appliedCheckoutPromo);
                $promos->assertUserCanRedeem($promo, auth()->user());
                $paidZar = $promos->previewDiscount($promo, $baseZar);
                $promos->redeemCheckoutDiscount(
                    $promo,
                    auth()->user(),
                    $credits,
                    $baseZar,
                    $paidZar,
                );
                $description = sprintf(
                    'Credit pack purchase (R%d → R%d, promo %s) [stub - no payment taken]',
                    $baseZar,
                    $paidZar,
                    $promo->code,
                );
            } catch (PromoCodeException $e) {
                $this->addError('checkoutPromoCode', $e->getMessage());
                $this->clearCheckoutPromo();

                return;
            }

            $this->clearCheckoutPromo();
        }

        auth()->user()->addCredits($credits, $description);

        session()->flash('status', $credits.' credits added to your account.');
    }

    public function render(CreditsPricing $pricing)
    {
        return view('livewire.billing', [
            'user' => auth()->user(),
            'packs' => BillingController::PACKS,
            'transactions' => auth()->user()->creditTransactions()->limit(25)->get(),
            'catalog' => $pricing->catalog(),
        ])->extends('layouts.app');
    }
}
