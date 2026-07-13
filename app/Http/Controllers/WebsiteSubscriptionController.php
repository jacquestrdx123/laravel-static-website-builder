<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteSubscriptionController extends Controller
{
    public function show(Request $request, Website $website): View
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        $subscription = $website->subscriptions()
            ->where('type', WebsiteSubscription::TYPE_MANUAL_EDITING)
            ->latest()
            ->first();

        return view('websites.subscription', [
            'website' => $website,
            'subscription' => $subscription,
            'price' => config('sites.editing_subscription_price'),
        ]);
    }

    public function purchase(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        $years = (int) config('sites.editing_subscription_years', 1);
        $startsAt = now();
        $expiresAt = now()->addYears($years);

        $active = $website->subscriptions()
            ->where('type', WebsiteSubscription::TYPE_MANUAL_EDITING)
            ->where('status', WebsiteSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->first();

        if ($active) {
            $startsAt = $active->starts_at;
            $expiresAt = $active->expires_at->addYears($years);
            $active->update([
                'expires_at' => $expiresAt,
                'note' => '[stub - no payment taken] Extended '.$years.' year(s)',
            ]);
        } else {
            WebsiteSubscription::create([
                'user_id' => $request->user()->id,
                'website_id' => $website->id,
                'type' => WebsiteSubscription::TYPE_MANUAL_EDITING,
                'status' => WebsiteSubscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'note' => '[stub - no payment taken]',
            ]);
        }

        return redirect()->route('websites.subscription.show', $website)
            ->with('status', 'Manual editing subscription active until '.$expiresAt->format('Y-m-d').'.');
    }
}
