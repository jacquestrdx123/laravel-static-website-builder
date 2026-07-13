<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\SiteContentUpdater;
use App\Services\WebsiteContentVault;
use App\Services\WebsiteGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateWebsiteJob implements ShouldQueue
{
    use Queueable;

    /** A generation can legitimately run for several minutes. */
    public int $timeout = 900;

    /** The API call is expensive - don't blindly retry on failure. */
    public int $tries = 1;

    /** @param  array<string, mixed>|null  $settingsBefore */
    public function __construct(public Website $website, public ?array $settingsBefore = null)
    {
    }

    public function handle(WebsiteGenerator $generator, SiteContentUpdater $updater): void
    {
        $this->website->update(['status' => Website::STATUS_GENERATING, 'error' => null]);

        try {
            $generator->generate($this->website);
        } catch (Throwable $e) {
            $this->markFailed($e);

            return;
        }

        $this->website->refresh();

        $this->website->update([
            'status' => Website::STATUS_READY,
            'generated_at' => now(),
        ]);

        try {
            $vault = WebsiteContentVault::forWebsite($this->website);
            $vault->recordProductSnapshot('website_generation', [
                'settings' => $this->settingsBefore ?? [],
            ], [
                'settings' => $this->website->settings,
                'offerings_live' => $updater->readOfferingsFromSite($this->website),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to record product snapshot after generation', [
                'website_id' => $this->website->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed($e);
    }

    private function markFailed(?Throwable $e): void
    {
        Log::error('Website generation failed', [
            'website_id' => $this->website->id,
            'exception' => $e?->getMessage(),
        ]);

        $this->website->update([
            'status' => Website::STATUS_FAILED,
            'error' => $e?->getMessage() ?? 'Generation failed unexpectedly.',
        ]);

        $this->website->user->addCredits(
            config('sites.generation_cost'),
            'Refund: generation failed for "'.$this->website->name.'"'
        );
    }
}
