<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteImage extends Model
{
    protected $fillable = ['website_id', 'path', 'original_name', 'description', 'mime_type', 'sort'];

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
}
