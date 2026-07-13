<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWebsiteAccess;
use App\Models\NewsletterSubscriber;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterSubscriberController extends Controller
{
    use AuthorizesWebsiteAccess;

    public function index(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);

        return view('websites.subscribers.index', [
            'website' => $website,
            'subscribers' => $website->newsletterSubscribers()->latest()->get(),
        ]);
    }

    public function store(Request $request, Website $website): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

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

        return back()->with('status', 'Subscriber added.');
    }

    public function destroy(Request $request, Website $website, NewsletterSubscriber $subscriber): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);
        abort_unless($subscriber->website_id === $website->id, 404);

        $subscriber->delete();

        return back()->with('status', 'Subscriber removed.');
    }
}
