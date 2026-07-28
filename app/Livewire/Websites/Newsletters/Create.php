<?php

namespace App\Livewire\Websites\Newsletters;

use App\Jobs\GenerateNewsletterJob;
use App\Models\Website;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Create extends Component
{
    #[Locked]
    public int $websiteId;

    public string $topic = '';

    public string $angle = '';

    public function mount(Website $website): void
    {
        abort_unless($website->user_id === auth()->id(), 403);
        $this->websiteId = $website->id;
    }

    public function generate(): mixed
    {
        $website = Website::query()->findOrFail($this->websiteId);

        $data = $this->validate([
            'topic' => ['required', 'string', 'max:255'],
            'angle' => ['nullable', 'string', 'max:500'],
        ]);

        $creditCost = (int) config('sites.newsletter_generation_cost');

        try {
            auth()->user()->spendCredits($creditCost, 'Newsletter generation: '.$website->name);
        } catch (RuntimeException) {
            session()->flash('error', 'You need '.$creditCost.' credits to generate a newsletter.');

            return $this->redirect(route('billing.index'), navigate: true);
        }

        GenerateNewsletterJob::dispatch($website, $data['topic'], $data['angle'] ?: null, $creditCost);

        session()->flash('status', 'Newsletter generation queued. Refresh in a moment.');

        return $this->redirect(route('websites.newsletters.index', $website), navigate: true);
    }

    public function render()
    {
        $website = Website::query()->findOrFail($this->websiteId);

        return view('livewire.websites.newsletters.create', [
            'website' => $website,
            'creditCost' => config('sites.newsletter_generation_cost'),
        ])->extends('layouts.app')->title('Generate newsletter — '.$website->name);
    }
}
