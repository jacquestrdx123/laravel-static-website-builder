<?php

namespace App\Livewire\Websites\Newsletters;

use App\Jobs\SendNewsletterJob;
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
        abort_if($vault->findNewsletter($uuid) === null, 404);

        $this->websiteId = $website->id;
        $this->uuid = $uuid;
    }

    public function send(): void
    {
        $website = Website::query()->findOrFail($this->websiteId);
        $vault = WebsiteContentVault::forWebsite($website);
        abort_if($vault->findNewsletter($this->uuid) === null, 404);

        SendNewsletterJob::dispatch($website, $this->uuid);
        session()->flash('status', 'Newsletter send queued.');
    }

    public function render()
    {
        $website = Website::query()->findOrFail($this->websiteId);
        $vault = WebsiteContentVault::forWebsite($website);
        $newsletter = $vault->findNewsletter($this->uuid);
        abort_if($newsletter === null, 404);

        return view('livewire.websites.newsletters.show', [
            'website' => $website,
            'newsletter' => $newsletter,
            'html' => $vault->newsletterHtml($this->uuid),
        ])->extends('layouts.app')->title('Newsletter — '.$website->name);
    }
}
