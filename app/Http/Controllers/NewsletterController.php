<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWebsiteAccess;
use App\Jobs\GenerateNewsletterJob;
use App\Jobs\SendNewsletterJob;
use App\Models\Website;
use App\Services\WebsiteContentVault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NewsletterController extends Controller
{
    use AuthorizesWebsiteAccess;

    public function index(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);

        return view('websites.newsletters.index', [
            'website' => $website,
            'newsletters' => $vault->listNewsletters(),
        ]);
    }

    public function create(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);

        return view('websites.newsletters.create', [
            'website' => $website,
            'creditCost' => config('sites.newsletter_generation_cost'),
        ]);
    }

    public function store(Request $request, Website $website): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'angle' => ['nullable', 'string', 'max:500'],
        ]);

        $creditCost = (int) config('sites.newsletter_generation_cost');

        try {
            $request->user()->spendCredits($creditCost, 'Newsletter generation: '.$website->name);
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need '.$creditCost.' credits to generate a newsletter.');
        }

        GenerateNewsletterJob::dispatch($website, $data['topic'], $data['angle'] ?? null, $creditCost);

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

    public function send(Request $request, Website $website, string $uuid): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);
        abort_if($vault->findNewsletter($uuid) === null, 404);

        SendNewsletterJob::dispatch($website, $uuid);

        return redirect()->route('websites.newsletters.show', [$website, $uuid])
            ->with('status', 'Newsletter send queued.');
    }
}
