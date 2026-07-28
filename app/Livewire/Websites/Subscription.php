<?php

namespace App\Livewire\Websites;

use App\Models\Website;
use App\Models\WebsiteSubscription;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Subscription extends Component
{
    #[Locked]
    public int $websiteId;

    public function mount(Website $website): void
    {
        abort_unless($website->user_id === auth()->id(), 403);
        $this->websiteId = $website->id;
    }

    public function purchase(): void
    {
        $website = $this->website();
        $years = (int) config('sites.editing_subscription_years', 1);
        $startsAt = now();
        $expiresAt = now()->addYears($years);

        $active = $website->subscriptions()
            ->where('type', WebsiteSubscription::TYPE_MANUAL_EDITING)
            ->where('status', WebsiteSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->first();

        if ($active) {
            $expiresAt = $active->expires_at->addYears($years);
            $active->update([
                'expires_at' => $expiresAt,
                'note' => '[stub - no payment taken] Extended '.$years.' year(s)',
            ]);
        } else {
            WebsiteSubscription::create([
                'user_id' => auth()->id(),
                'website_id' => $website->id,
                'type' => WebsiteSubscription::TYPE_MANUAL_EDITING,
                'status' => WebsiteSubscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'note' => '[stub - no payment taken]',
            ]);
        }

        session()->flash('status', 'Manual editing subscription active until '.$expiresAt->format('Y-m-d').'.');
    }

    public function render()
    {
        $website = $this->website();
        $subscription = $website->subscriptions()
            ->where('type', WebsiteSubscription::TYPE_MANUAL_EDITING)
            ->latest()
            ->first();

        return view('livewire.websites.subscription', [
            'website' => $website,
            'subscription' => $subscription,
            'price' => config('sites.editing_subscription_price'),
        ])->extends('layouts.app')->title('Subscription — '.$website->name);
    }

    private function website(): Website
    {
        return Website::query()->findOrFail($this->websiteId);
    }
}
