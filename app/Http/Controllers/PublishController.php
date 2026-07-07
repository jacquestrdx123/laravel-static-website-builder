<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PublishController extends Controller
{
    /**
     * Publish the generated site: copy it into the Caddy-served web root.
     *
     * TODO: gate this behind a hosting subscription/payment once the
     * billing provider is integrated.
     */
    public function store(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 409);

        $target = config('sites.publish_path').'/'.$website->slug;

        File::deleteDirectory($target);
        File::ensureDirectoryExists(dirname($target));
        File::copyDirectory($website->sitePath(), $target);

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

        File::deleteDirectory(config('sites.publish_path').'/'.$website->slug);

        $website->update([
            'status' => Website::STATUS_READY,
            'published_at' => null,
        ]);

        return redirect()->route('websites.show', $website)
            ->with('status', 'Site unpublished.');
    }
}
