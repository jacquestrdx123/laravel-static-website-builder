<?php

namespace App\Livewire\Websites;

use App\Models\NewsletterSubscriber;
use App\Models\Website;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Subscribers extends Component
{
    #[Locked]
    public int $websiteId;

    public string $email = '';

    public string $name = '';

    public function mount(Website $website): void
    {
        abort_unless($website->user_id === auth()->id(), 403);
        $this->websiteId = $website->id;
    }

    public function add(): void
    {
        $data = $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            [
                'website_id' => $this->websiteId,
                'email' => strtolower($data['email']),
            ],
            [
                'name' => $data['name'] ?: null,
                'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]
        );

        $this->reset('email', 'name');
        session()->flash('status', 'Subscriber added.');
    }

    public function remove(int $subscriberId): void
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('website_id', $this->websiteId)
            ->whereKey($subscriberId)
            ->firstOrFail();

        $subscriber->delete();
        session()->flash('status', 'Subscriber removed.');
    }

    public function render()
    {
        $website = $this->website();

        return view('livewire.websites.subscribers', [
            'website' => $website,
            'subscribers' => $website->newsletterSubscribers()->latest()->get(),
        ])->extends('layouts.app')->title('Subscribers — '.$website->name);
    }

    private function website(): Website
    {
        return Website::query()->findOrFail($this->websiteId);
    }
}
