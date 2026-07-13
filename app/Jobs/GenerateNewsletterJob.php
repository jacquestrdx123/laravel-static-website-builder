<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\NewsletterGenerator;
use App\Services\WebsiteContentVault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateNewsletterJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public Website $website,
        public string $topic,
        public ?string $angle,
        public int $creditCost,
    ) {
    }

    public function handle(NewsletterGenerator $generator): void
    {
        try {
            $result = $generator->generate($this->website, $this->topic, $this->angle);

            $vault = WebsiteContentVault::forWebsite($this->website);
            $vault->recordNewsletter(
                $this->topic,
                [
                    'topic' => $this->topic,
                    'angle' => $this->angle,
                    'settings' => $this->website->settings,
                ],
                ['subject' => $result['subject']],
                $result['html'],
                $result['text'],
            );
        } catch (Throwable $e) {
            $this->website->user->addCredits(
                $this->creditCost,
                'Refund: newsletter generation failed for "'.$this->website->name.'"'
            );

            Log::error('Newsletter generation failed', [
                'website_id' => $this->website->id,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
