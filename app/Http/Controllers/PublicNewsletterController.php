<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicNewsletterController extends Controller
{
    public function subscribe(Request $request, string $slug): RedirectResponse
    {
        $website = Website::where('slug', $slug)
            ->where('status', Website::STATUS_PUBLISHED)
            ->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            [
                'website_id' => $website->id,
                'email' => strtolower($data['email']),
            ],
            [
                'name' => $data['name'] ?? null,
                'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]
        );

        return back()->with('status', 'You are subscribed.');
    }

    public function unsubscribe(string $slug, string $token): View
    {
        $website = Website::where('slug', $slug)->firstOrFail();

        $subscriber = NewsletterSubscriber::where('website_id', $website->id)
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $subscriber->update([
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);

        return view('public.newsletter-unsubscribed', [
            'website' => $website,
        ]);
    }
}
