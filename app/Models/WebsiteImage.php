<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WebsiteImage extends Model
{
    public const TYPE_LOGO = 'logo';

    public const TYPE_FAVICON = 'favicon';

    public const TYPE_BANNER = 'banner';

    public const TYPE_GALLERY = 'gallery';

    public const TYPE_PRODUCT = 'product';

    protected $fillable = ['website_id', 'asset_key', 'path', 'original_name', 'type', 'description', 'mime_type', 'sort'];

    protected static function booted(): void
    {
        static::creating(function (WebsiteImage $image) {
            $image->asset_key ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** Filename the image is exposed as inside the generated site. */
    public function assetName(): string
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        return 'image-'.($this->sort + 1).'.'.$extension;
    }

    /** Authenticated preview URL for use in the app UI. */
    public function previewUrl(): string
    {
        return route('websites.images.show', [$this->website_id, $this->id]);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_LOGO => 'Logo',
            self::TYPE_FAVICON => 'Favicon',
            self::TYPE_BANNER => 'Banner',
            self::TYPE_GALLERY => 'Gallery',
            self::TYPE_PRODUCT => 'Product',
            default => ucfirst($this->type ?? 'Photo'),
        };
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk('local')->exists($this->path);
    }
}
