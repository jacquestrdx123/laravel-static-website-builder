<?php

namespace App\Jobs;

use App\Mail\WebsiteNewsletter;
use App\Models\NewsletterSubscriber;
use App\Models\Website;
use App\Services\WebsiteContentVault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewsletterJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public Website $website,
        public string $newsletterUuid,
    ) {
    }

    public function handle(): void
    {
        $vault = WebsiteContentVault::forWebsite($this->website);
        $newsletter = $vault->findNewsletter($this->newsletterUuid);

        if ($newsletter === null) {
            return;
        }

        $html = $vault->newsletterHtml($this->newsletterUuid);
        if ($html === null) {
            return;
        }

        $subscribers = NewsletterSubscriber::query()
            ->where('website_id', $this->website->id)
            ->where('status', NewsletterSubscriber::STATUS_SUBSCRIBED)
            ->get();

        $sent = 0;

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->send(new WebsiteNewsletter(
                $this->website,
                (string) $newsletter['subject'],
                $html,
                $subscriber,
            ));
            $sent++;
        }

        $vault->markNewsletterSent($this->newsletterUuid, $sent);
    }
}
