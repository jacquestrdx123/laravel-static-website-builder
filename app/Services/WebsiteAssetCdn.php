<?php

namespace App\Services;

use App\Models\Website;
use App\Models\WebsiteImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Publishes website uploads to stable, publicly cacheable CDN URLs.
 *
 * Files live at: {cdn_disk}/cdn/{website_id}/{asset_key}.{ext}
 * Served via:    GET /cdn/{website}/{asset_key}
 */
class WebsiteAssetCdn
{
    public function __construct(private Website $website)
    {
    }

    public static function forWebsite(Website $website): self
    {
        return new self($website);
    }

    /** Ensure the image has an asset_key and a published CDN copy. Returns the key. */
    public function publish(WebsiteImage $image): string
    {
        if ($image->website_id !== $this->website->id) {
            throw new RuntimeException('Image does not belong to this website.');
        }

        if (! $image->existsOnDisk()) {
            throw new RuntimeException('Image file is missing on disk.');
        }

        if (blank($image->asset_key)) {
            $image->asset_key = (string) Str::uuid();
            $image->save();
        }

        $extension = strtolower(pathinfo($image->path, PATHINFO_EXTENSION) ?: 'jpg');
        $relative = $this->relativePath($image->asset_key, $extension);

        $disk = Storage::disk(config('sites.cdn_disk'));
        File::ensureDirectoryExists($disk->path(dirname($relative)));

        $disk->put(
            $relative,
            Storage::disk('local')->get($image->path),
            ['visibility' => 'public']
        );

        return $image->asset_key;
    }

    public function url(string $assetKey): string
    {
        return rtrim((string) config('sites.cdn_url'), '/')
            .'/cdn/'.$this->website->id.'/'.$assetKey;
    }

    public function urlForImage(?WebsiteImage $image): ?string
    {
        if ($image === null || blank($image->asset_key)) {
            return null;
        }

        return $this->url($image->asset_key);
    }

    /** @return array<string, string> asset_key => absolute CDN URL */
    public function urlMap(): array
    {
        $map = [];

        foreach ($this->website->images as $image) {
            if (filled($image->asset_key)) {
                $map[$image->asset_key] = $this->url($image->asset_key);
            }
        }

        return $map;
    }

    public function relativePath(string $assetKey, string $extension): string
    {
        $prefix = trim((string) config('sites.cdn_path_prefix'), '/');

        return $prefix.'/'.$this->website->id.'/'.$assetKey.'.'.ltrim($extension, '.');
    }

    public function absolutePath(string $assetKey, string $extension): string
    {
        return Storage::disk(config('sites.cdn_disk'))->path(
            $this->relativePath($assetKey, $extension)
        );
    }

    public function resolveImageByAssetKey(string $assetKey): ?WebsiteImage
    {
        return $this->website->images()->where('asset_key', $assetKey)->first();
    }
}
