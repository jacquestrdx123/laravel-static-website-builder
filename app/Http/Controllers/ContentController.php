<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\SiteContentUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Free, instant edits to the business data of an already-generated site:
 * offerings (services/products/menu), tagline, and contact email. Rewrites
 * the annotated elements in the static HTML - no AI call, no credits.
 */
class ContentController extends Controller
{
    public function edit(Request $request, Website $website, SiteContentUpdater $updater): View
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

        return view('websites.content', [
            'website' => $website,
            'editable' => $updater->supportsEditing($website),
        ]);
    }

    public function update(Request $request, Website $website, SiteContentUpdater $updater): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

        $data = $request->validate([
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'offering_type' => ['required', 'in:'.implode(',', WebsiteController::OFFERING_TYPES)],
            'offerings' => ['nullable', 'array', 'max:'.WebsiteController::MAX_OFFERINGS],
            'offerings.*.name' => ['nullable', 'string', 'max:100'],
            'offerings.*.description' => ['nullable', 'string', 'max:500'],
            'offerings.*.price' => ['nullable', 'string', 'max:50'],
        ]);

        $offerings = array_values(array_filter(
            $data['offerings'] ?? [],
            fn ($offering) => filled($offering['name'] ?? null)
        ));

        $website->update([
            'settings' => array_merge($website->settings, [
                'tagline' => $data['tagline'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'offering_type' => $data['offering_type'],
                'offerings' => array_map(fn ($offering) => [
                    'name' => $offering['name'],
                    'description' => $offering['description'] ?? null,
                    'price' => $offering['price'] ?? null,
                ], $offerings),
            ]),
        ]);

        $changed = $updater->apply($website);

        if ($changed === 0) {
            return redirect()->route('websites.content.edit', $website)
                ->with('error', 'Saved, but this site was generated before content editing existed - regenerate it once to enable live updates.');
        }

        return redirect()->route('websites.show', $website)
            ->with('status', 'Content updated on your site - no credits used.');
    }
}
