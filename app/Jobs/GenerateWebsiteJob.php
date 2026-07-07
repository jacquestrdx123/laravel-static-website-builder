<?php

namespace App\Jobs;

use App\Models\Website;
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

    public function __construct(public Website $website)
    {
    }

    public function handle(WebsiteGenerator $generator): void
    {
        $this->website->update(['status' => Website::STATUS_GENERATING, 'error' => null]);

        try {
            $generator->generate($this->website);
        } catch (Throwable $e) {
            $this->markFailed($e);

            return;
        }

        $this->website->update([
            'status' => Website::STATUS_READY,
            'generated_at' => now(),
        ]);
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

        // The customer shouldn't pay for a failed generation.
        $this->website->user->addCredits(
            config('sites.generation_cost'),
            'Refund: generation failed for "'.$this->website->name.'"'
        );
    }
}
