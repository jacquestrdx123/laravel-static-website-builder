<?php

namespace App\Livewire\Websites;

use App\Jobs\GenerateWebsiteJob;
use App\Models\Website;
use App\Services\PublishedSiteHost;
use App\Services\WebsiteContentVault;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Show extends Component
{
    #[Locked]
    public int $websiteId;

    public string $status = '';

    public ?string $error = null;

    public function mount(Website $website): void
    {
        abort_unless($website->user_id === auth()->id(), 403);

        $this->websiteId = $website->id;
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $website = $this->website();
        $previous = $this->status;
        $this->status = $website->status;
        $this->error = $website->error;

        if ($previous !== '' && $previous !== $this->status
            && ! in_array($this->status, [Website::STATUS_QUEUED, Website::STATUS_GENERATING], true)) {
            // Force a full re-render once generation finishes so the preview iframe appears.
        }
    }

    public function regenerate(): mixed
    {
        $website = $this->website();
        abort_if($website->isBusy(), 409);

        try {
            auth()->user()->spendCredits(
                config('sites.generation_cost'),
                'AI regeneration for "'.$website->name.'"'
            );
        } catch (RuntimeException) {
            session()->flash('error', 'You need more credits to regenerate this website.');

            return $this->redirect(route('billing.index'), navigate: true);
        }

        $website->update(['status' => Website::STATUS_QUEUED, 'error' => null]);
        GenerateWebsiteJob::dispatch($website, $website->settings);

        session()->flash('status', 'Your website is being regenerated.');
        $this->refreshStatus();

        return null;
    }

    public function destroyWebsite(): mixed
    {
        $website = $this->website();

        File::deleteDirectory($website->sitePath());
        File::deleteDirectory(config('sites.publish_path').'/'.$website->slug);
        Storage::disk('local')->deleteDirectory('uploads/'.$website->id);
        $website->delete();

        session()->flash('status', 'Website deleted.');

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function publish(PublishedSiteHost $host): void
    {
        $website = $this->website();
        abort_unless($website->isGenerated(), 409);

        $host->publish($website);
        $website->update([
            'status' => Website::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->refreshStatus();
        session()->flash('status', 'Published! Your site is live at '.$website->hostname());
    }

    public function unpublish(PublishedSiteHost $host): void
    {
        $website = $this->website();

        $host->unpublish($website);
        $website->update([
            'status' => Website::STATUS_READY,
            'published_at' => null,
        ]);

        $this->refreshStatus();
        session()->flash('status', 'Site unpublished.');
    }

    public function render()
    {
        $website = $this->website();
        $vault = WebsiteContentVault::forWebsite($website);

        return view('livewire.websites.show', [
            'website' => $website,
            'vaultCounts' => [
                'newsletters' => count($vault->listNewsletters()),
                'posters' => count($vault->listPosters()),
                'snapshots' => count($vault->listProductSnapshots()),
            ],
        ])->extends('layouts.app')->title($website->name);
    }

    private function website(): Website
    {
        return Website::query()->findOrFail($this->websiteId);
    }
}
