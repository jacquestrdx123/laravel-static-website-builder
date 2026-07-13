<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesWebsiteAccess;
use App\Jobs\GeneratePosterJob;
use App\Models\Website;
use App\Services\WebsiteContentVault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class PosterController extends Controller
{
    use AuthorizesWebsiteAccess;

    public function index(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);

        return view('websites.posters.index', [
            'website' => $website,
            'posters' => $vault->listPosters(),
        ]);
    }

    public function create(Request $request, Website $website): View
    {
        $this->authorizeWebsite($request, $website);

        return view('websites.posters.create', [
            'website' => $website,
            'formats' => config('sites.poster_formats'),
            'creditCost' => config('sites.poster_generation_cost'),
        ]);
    }

    public function store(Request $request, Website $website): RedirectResponse
    {
        $this->authorizeWebsite($request, $website);

        $data = $request->validate([
            'brief' => ['required', 'string', 'max:1000'],
            'format' => ['required', 'string', 'in:'.implode(',', array_keys(config('sites.poster_formats')))],
        ]);

        $creditCost = (int) config('sites.poster_generation_cost');

        try {
            $request->user()->spendCredits($creditCost, 'Poster generation: '.$website->name);
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need '.$creditCost.' credits to generate a poster.');
        }

        GeneratePosterJob::dispatch($website, $data['brief'], $data['format'], $creditCost);

        return redirect()->route('websites.posters.index', $website)
            ->with('status', 'Poster generation queued. Refresh in a moment.');
    }

    public function show(Request $request, Website $website, string $uuid): View
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);
        $poster = $vault->findPoster($uuid);

        abort_if($poster === null, 404);

        return view('websites.posters.show', [
            'website' => $website,
            'poster' => $poster,
            'html' => $vault->posterHtml($uuid),
        ]);
    }

    public function download(Request $request, Website $website, string $uuid): Response
    {
        $this->authorizeWebsite($request, $website);

        $vault = WebsiteContentVault::forWebsite($website);
        $path = $vault->posterPngPath($uuid);

        abort_if($path === null, 404);

        return response()->file($path, [
            'Content-Type' => 'image/png',
        ]);
    }
}
