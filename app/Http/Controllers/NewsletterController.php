<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWebsiteAccess;
use App\Jobs\GenerateNewsletterJob;
use App\Jobs\SendNewsletterJob;
use App\Models\Website;
use App\Services\CreditBilling;
use App\Services\WebsiteContentVault;
use App\Support\CreditsPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NewsletterController extends Controller
{
    use AuthorizesWebsiteAccess;

    public function index(Request $request, Website $website, CreditsPricing $pricing): View
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);

        return view('websites.newsletters.index', [
            'website' => $website,
            'newsletters' => $vault->listNewsletters(),
            'hasNewsletterHosting' => $website->hasActiveNewsletterHosting(),
            'hostingCredits' => $pricing->newsletterHostingCreditsPerMonth(),
            'includedEmails' => $pricing->newsletterIncludedEmailsPerMonth(),
        ]);
    }

    public function create(Request $request, Website $website, CreditsPricing $pricing): View
    {
        $this->authorizeWebsite($request, $website);

        return view('websites.newsletters.create', [
            'website' => $website,
            'hasNewsletterHosting' => $website->hasActiveNewsletterHosting(),
            'hostingCredits' => $pricing->newsletterHostingCreditsPerMonth(),
            'hostingZar' => $pricing->formatZar($pricing->newsletterHostingCreditsPerMonth()),
            'includedEmails' => $pricing->newsletterIncludedEmailsPerMonth(),
        ]);
    }

    public function store(Request $request, Website $website, CreditBilling $billing): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'angle' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $billing->ensureNewsletterHosting($request->user(), $website);
        } catch (RuntimeException) {
            $credits = app(CreditsPricing::class)->newsletterHostingCreditsPerMonth();

            return redirect()->route('billing.index')
                ->with('error', 'You need '.$credits.' credits for newsletter hosting this month.');
        }

        // AI draft is included with newsletter hosting — no extra charge; no refund on failure.
        GenerateNewsletterJob::dispatch($website, $data['topic'], $data['angle'] ?? null, 0);

        return redirect()->route('websites.newsletters.index', $website)
            ->with('status', 'Newsletter generation queued. Refresh in a moment.');
    }

    public function show(Request $request, Website $website, string $uuid): View
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);
        $newsletter = $vault->findNewsletter($uuid);

        abort_if($newsletter === null, 404);

        return view('websites.newsletters.show', [
            'website' => $website,
            'newsletter' => $newsletter,
            'html' => $vault->newsletterHtml($uuid),
        ]);
    }

    public function send(Request $request, Website $website, string $uuid, CreditBilling $billing, CreditsPricing $pricing): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);
        abort_if($vault->findNewsletter($uuid) === null, 404);

        if (! $website->hasActiveNewsletterHosting()) {
            return redirect()->route('websites.newsletters.create', $website)
                ->with('error', 'Newsletter hosting is required before sending.');
        }

        $recipientCount = $website->newsletterSubscribers()
            ->where('status', \App\Models\NewsletterSubscriber::STATUS_SUBSCRIBED)
            ->count();

        try {
            $billing->chargeNewsletterOverage($request->user(), $website, $recipientCount);
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need more credits for newsletter email overage ('
                    .$pricing->formatCredits($pricing->newsletterExtraBlockCredits())
                    .' per '.$pricing->newsletterExtraBlockSize().' emails beyond '
                    .$pricing->newsletterIncludedEmailsPerMonth().').');
        }

        SendNewsletterJob::dispatch($website, $uuid);

        return redirect()->route('websites.newsletters.show', [$website, $uuid])
            ->with('status', 'Newsletter send queued.');
    }
}
