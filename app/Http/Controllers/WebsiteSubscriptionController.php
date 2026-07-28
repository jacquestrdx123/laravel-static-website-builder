<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteSubscription;
use App\Services\CreditBilling;
use App\Support\CreditsPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class WebsiteSubscriptionController extends Controller
{
    public function show(Request $request, Website $website, CreditsPricing $pricing): View
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        $subscription = $website->subscriptions()
            ->where('type', WebsiteSubscription::TYPE_MANUAL_EDITING)
            ->latest()
            ->first();

        return view('websites.subscription', [
            'website' => $website,
            'subscription' => $subscription,
            'creditsPerYear' => $pricing->editingCreditsPerYear(),
            'creditsPerMonth' => $pricing->editingCreditsPerMonth(),
            'priceZar' => $pricing->formatZar($pricing->editingCreditsPerYear()).'/year',
            'priceMonthlyZar' => $pricing->formatZar($pricing->editingCreditsPerMonth()).'/month',
        ]);
    }

    public function purchase(Request $request, Website $website, CreditBilling $billing, CreditsPricing $pricing): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        $years = (int) config('sites.editing_subscription_years', 1);

        try {
            $subscription = $billing->purchaseEditingYear($request->user(), $website, $years);
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need '.$pricing->editingCreditsPerYear() * $years
                    .' credits ('.$pricing->formatZar($pricing->editingCreditsPerYear() * $years)
                    .') for editing without AI.');
        }

        return redirect()->route('websites.subscription.show', $website)
            ->with('status', 'Editing subscription active until '.$subscription->expires_at->format('Y-m-d').'.');
    }
}
