<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\PublishedSiteHost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublishController extends Controller
{
    public function __construct(private PublishedSiteHost $host)
    {
    }

    /**
     * Publish the generated site: copy it into the Caddy-served web root.
     */
    public function store(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 409);

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
