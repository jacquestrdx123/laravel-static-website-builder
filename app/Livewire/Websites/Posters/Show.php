<?php

namespace App\Livewire\Websites\Posters;

use App\Models\Website;
use App\Services\WebsiteContentVault;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $websiteId;

    #[Locked]
    public string $uuid;

    public function mount(Website $website, string $uuid): void
    {
        abort_unless($website->user_id === auth()->id(), 403);

        $vault = WebsiteContentVault::forWebsite($website);
        abort_if($vault->findPoster($uuid) === null, 404);

        $this->websiteId = $website->id;
        $this->uuid = $uuid;
    }

    public function render()
    {
        $website = Website::query()->findOrFail($this->websiteId);
        $vault = WebsiteContentVault::forWebsite($website);
        $poster = $vault->findPoster($this->uuid);
        abort_if($poster === null, 404);

        return view('livewire.websites.posters.show', [
            'website' => $website,
            'poster' => $poster,
            'html' => $vault->posterHtml($this->uuid),
        ])->extends('layouts.app')->title('Poster — '.$website->name);
    }
}
