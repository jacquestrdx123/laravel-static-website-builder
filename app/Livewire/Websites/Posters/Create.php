<?php

namespace App\Livewire\Websites\Posters;

use App\Jobs\GeneratePosterJob;
use App\Models\Website;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Create extends Component
{
    #[Locked]
    public int $websiteId;

    public string $brief = '';

    public string $format = '';

    public function mount(Website $website): void
    {
        abort_unless($website->user_id === auth()->id(), 403);
        $this->websiteId = $website->id;
        $formats = array_keys(config('sites.poster_formats'));
        $this->format = $formats[0] ?? '';
    }

    public function generate(): mixed
    {
        $website = Website::query()->findOrFail($this->websiteId);

        $data = $this->validate([
            'brief' => ['required', 'string', 'max:1000'],
            'format' => ['required', 'string', 'in:'.implode(',', array_keys(config('sites.poster_formats')))],
        ]);

        $creditCost = (int) config('sites.poster_generation_cost');

        try {
            auth()->user()->spendCredits($creditCost, 'Poster generation: '.$website->name);
        } catch (RuntimeException) {
            session()->flash('error', 'You need '.$creditCost.' credits to generate a poster.');

            return $this->redirect(route('billing.index'), navigate: true);
        }

        GeneratePosterJob::dispatch($website, $data['brief'], $data['format'], $creditCost);

        session()->flash('status', 'Poster generation queued. Refresh in a moment.');

        return $this->redirect(route('websites.posters.index', $website), navigate: true);
    }

    public function render()
    {
        $website = Website::query()->findOrFail($this->websiteId);

        return view('livewire.websites.posters.create', [
            'website' => $website,
            'formats' => config('sites.poster_formats'),
            'creditCost' => config('sites.poster_generation_cost'),
        ])->extends('layouts.app')->title('Generate poster — '.$website->name);
    }
}
