<?php

namespace App\Http\Controllers;

use App\Exceptions\PromoCodeException;
use App\Services\PromoCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    /** Credit packs offered on the billing page: credits => price metadata. */
    public const PACKS = [
        5 => ['price_zar' => 99, 'label' => 'R99'],
        15 => ['price_zar' => 249, 'label' => 'R249'],
        50 => ['price_zar' => 699, 'label' => 'R699'],
    ];

    public function index(Request $request): View
    {
        return view('billing.index', [
            'user' => $request->user(),
            'packs' => self::PACKS,
            'transactions' => $request->user()->creditTransactions()->limit(25)->get(),
        ]);
    }

    /**
     * Stubbed checkout: credits are added immediately.
     *
     * TODO: replace with a real payment gateway (Stripe / Paystack / PayFast).
     * The flow should become: create checkout session -> redirect -> webhook
     * verifies payment -> addCredits() from the webhook handler.
     */
    public function purchase(Request $request, PromoCodeService $promos): RedirectResponse
    {
        $data = $request->validate([
            'credits' => ['required', 'integer', 'in:'.implode(',', array_keys(self::PACKS))],
            'promo_code' => ['nullable', 'string', 'max:64'],
        ]);

        $packCredits = (int) $data['credits'];
        $pack = self::PACKS[$packCredits];
        $baseZar = $pack['price_zar'];
        $paidZar = $baseZar;
        $priceLabel = $pack['label'];
        $description = 'Credit pack purchase ('.$priceLabel.') [stub - no payment taken]';

        if (filled($data['promo_code'] ?? null)) {
            try {
                $promo = $promos->findValid($data['promo_code']);
                $promos->assertUserCanRedeem($promo, $request->user());
                $paidZar = $promos->previewDiscount($promo, $baseZar);
                $promos->redeemCheckoutDiscount(
                    $promo,
                    $request->user(),
                    $packCredits,
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
                return back()->withErrors(['promo_code' => $e->getMessage()]);
            }
        }

        $request->user()->addCredits($packCredits, $description);

        return redirect()->route('billing.index')
            ->with('status', $packCredits.' credits added to your account.');
    }
}
