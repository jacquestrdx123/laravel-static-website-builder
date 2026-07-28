<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\CreditBilling;
use App\Services\PublishedSiteHost;
use App\Support\CreditsPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PublishController extends Controller
{
    public function __construct(private PublishedSiteHost $host)
    {
    }

    /**
     * Publish the generated site: charge hosting if needed, then copy into the live web root.
     */
    public function store(Request $request, Website $website, CreditBilling $billing, CreditsPricing $pricing): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 409);

        try {
            $billing->ensureWebsiteHosting($request->user(), $website);
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need '.$pricing->websiteHostingCreditsPerMonth()
                    .' credits ('.$pricing->formatZar($pricing->websiteHostingCreditsPerMonth())
                    .'/month) to publish this website.');
        }

        $this->host->publish($website);

        $website->update([
            'status' => Website::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return redirect()->route('websites.show', $website)
            ->with('status', 'Published! Your site is live at '.$website->hostname());
    }

    public function destroy(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        $this->host->unpublish($website);

        $website->update([
            'status' => Website::STATUS_READY,
            'published_at' => null,
        ]);

        return redirect()->route('websites.show', $website)
            ->with('status', 'Site unpublished.');
    }
}
