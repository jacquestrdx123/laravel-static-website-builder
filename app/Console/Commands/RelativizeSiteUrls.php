<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Services\WebsiteGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RelativizeSiteUrls extends Command
{
    protected $signature = 'sites:relativize {slug? : Only fix this site}';

    protected $description = 'Rewrite root-absolute asset URLs to relative in already-generated sites';

    public function handle(WebsiteGenerator $generator): int
    {
        $websites = Website::query()
            ->when($this->argument('slug'), fn ($q, $slug) => $q->where('slug', $slug))
            ->get();

        foreach ($websites as $website) {
            $sitePath = $website->sitePath();

            if (! File::isDirectory($sitePath)) {
                $this->line("{$website->slug}: no generated files, skipping");

                continue;
            }

            $fixed = 0;

            foreach (File::allFiles($sitePath) as $file) {
                if (! in_array($file->getExtension(), ['html', 'htm', 'css'], true)) {
                    continue;
                }

                $relative = str_replace('\\', '/', $file->getRelativePathname());
                $original = File::get($file->getPathname());
                $rewritten = $generator->relativizeUrls($relative, $original);

                if ($rewritten !== $original) {
                    File::put($file->getPathname(), $rewritten);
                    $fixed++;
                }
            }

            $this->info("{$website->slug}: {$fixed} file(s) rewritten");

            // Published copies need the same treatment - re-publish from source.
            $published = config('sites.publish_path').'/'.$website->slug;
            if ($fixed > 0 && File::isDirectory($published)) {
                File::deleteDirectory($published);
                File::copyDirectory($sitePath, $published);
                $this->line("{$website->slug}: published copy refreshed");
            }
        }

        return self::SUCCESS;
    }
}
