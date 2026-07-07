<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    /** Credit packs offered on the billing page: credits => display price. */
    public const PACKS = [
        5 => 'R99',
        15 => 'R249',
        50 => 'R699',
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
    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'credits' => ['required', 'integer', 'in:'.implode(',', array_keys(self::PACKS))],
        ]);

        $request->user()->addCredits(
            (int) $data['credits'],
            'Credit pack purchase ('.self::PACKS[$data['credits']].') [stub - no payment taken]'
        );

        return redirect()->route('billing.index')
            ->with('status', $data['credits'].' credits added to your account.');
    }
}
