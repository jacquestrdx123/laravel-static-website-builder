<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\PosterExporter;
use App\Services\PosterGenerator;
use App\Services\WebsiteContentVault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePosterJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public Website $website,
        public string $brief,
        public string $format,
        public int $creditCost,
    ) {
    }

    public function handle(PosterGenerator $generator, PosterExporter $exporter): void
    {
        try {
            $html = $generator->generate($this->website, $this->brief, $this->format);
            $dimensions = config('sites.poster_formats.'.$this->format, ['width' => 1080, 'height' => 1080]);

            $vault = WebsiteContentVault::forWebsite($this->website);
            $uuid = $vault->recordPoster(
                [
                    'brief' => $this->brief,
                    'format' => $this->format,
                    'settings' => $this->website->settings,
                ],
                $this->format,
                $html,
                null,
            );

            $poster = $vault->findPoster($uuid);
            if ($poster === null) {
                return;
            }

            $root = rtrim((string) config('sites.website_data_path'), '/').'/'.$this->website->id;
            $pngAbsolute = $root.'/'.$poster['dir_path'].'/poster.png';

            try {
                $exporter->export($html, $pngAbsolute, (int) $dimensions['width'], (int) $dimensions['height']);
                $vault->updatePosterPng($uuid, $poster['dir_path'].'/poster.png');
            } catch (Throwable $e) {
                Log::warning('Poster PNG export failed; HTML saved in vault', [
                    'website_id' => $this->website->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        } catch (Throwable $e) {
            $this->website->user->addCredits(
                $this->creditCost,
                'Refund: poster generation failed for "'.$this->website->name.'"'
            );

            Log::error('Poster generation failed', [
                'website_id' => $this->website->id,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
