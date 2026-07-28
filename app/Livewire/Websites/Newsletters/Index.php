<?php

namespace App\Livewire\Websites\Newsletters;

use App\Models\Website;
use App\Services\WebsiteContentVault;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public int $websiteId;

    public function mount(Website $website): void
    {
        abort_unless($website->user_id === auth()->id(), 403);
        $this->websiteId = $website->id;
    }

    public function render()
    {
        $website = Website::query()->findOrFail($this->websiteId);
        $vault = WebsiteContentVault::forWebsite($website);

        return view('livewire.websites.newsletters.index', [
            'website' => $website,
            'newsletters' => $vault->listNewsletters(),
        ])->extends('layouts.app')->title('Newsletters — '.$website->name);
    }
}
