<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PosterExporter
{
    /**
     * Export poster HTML to PNG. Returns absolute path to PNG file.
     */
    public function export(string $html, string $targetPath, int $width, int $height): string
    {
        File::ensureDirectoryExists(dirname($targetPath));

        $htmlPath = $targetPath.'.html';
        File::put($htmlPath, $html);

        if (! class_exists(\Spatie\Browsershot\Browsershot::class)) {
            throw new RuntimeException('Browsershot is not installed. Run: composer require spatie/browsershot');
        }

        \Spatie\Browsershot\Browsershot::html($html)
            ->windowSize($width, $height)
            ->deviceScaleFactor(2)
            ->save($targetPath);

        return $targetPath;
    }
}
