<?php

namespace App\Services;

use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteSubscription;
use App\Support\CreditsPricing;
use Illuminate\Support\Carbon;

/**
 * Charges locked credit prices and activates per-website subscriptions.
 */
class CreditBilling
{
    public function __construct(private CreditsPricing $pricing)
    {
    }

    public function ensureWebsiteHosting(User $user, Website $website): void
    {
        if ($website->hasActiveHostingSubscription()) {
            return;
        }

        $credits = $this->pricing->websiteHostingCreditsPerMonth();
        $user->spendCredits($credits, 'Website hosting (1 month): '.$website->name);
        $this->activateSubscription(
            $user,
            $website,
            WebsiteSubscription::TYPE_WEBSITE_HOSTING,
            now()->addMonth(),
            'Charged '.$credits.' credits for 1 month hosting'
        );
    }

    public function ensureNewsletterHosting(User $user, Website $website): void
    {
        if ($website->hasActiveNewsletterHosting()) {
            return;
        }

        $credits = $this->pricing->newsletterHostingCreditsPerMonth();
        $user->spendCredits(
            $credits,
            'Newsletter hosting (1 month): '.$website->name
        );
        $this->activateSubscription(
            $user,
            $website,
            WebsiteSubscription::TYPE_NEWSLETTER_HOSTING,
            now()->addMonth(),
            'Charged '.$credits.' credits for 1 month newsletter hosting'
        );
    }

    public function purchaseEditingYear(User $user, Website $website, int $years = 1): WebsiteSubscription
    {
        $years = max(1, $years);
        $credits = $this->pricing->editingCreditsPerYear() * $years;
        $user->spendCredits(
            $credits,
            'Editing without AI ('.$years.' year'.($years === 1 ? '' : 's').'): '.$website->name
        );

        $active = $website->activeSubscription(WebsiteSubscription::TYPE_MANUAL_EDITING);
        $startsAt = $active?->starts_at ?? now();
        $expiresAt = ($active?->expires_at && $active->expires_at->isFuture()
            ? $active->expires_at
            : now())->copy()->addYears($years);

        if ($active) {
            $active->update([
                'expires_at' => $expiresAt,
                'status' => WebsiteSubscription::STATUS_ACTIVE,
                'note' => 'Extended '.$years.' year(s) for '.$credits.' credits',
            ]);

            return $active->fresh();
        }

        return WebsiteSubscription::create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'type' => WebsiteSubscription::TYPE_MANUAL_EDITING,
            'status' => WebsiteSubscription::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'note' => 'Purchased for '.$credits.' credits',
        ]);
    }

    /**
     * Charge overage for emails beyond the monthly included allowance.
     * Counts sends already marked on vault newsletters this calendar month,
     * plus $additionalRecipients about to be sent.
     */
    public function chargeNewsletterOverage(User $user, Website $website, int $additionalRecipients): int
    {
        if ($additionalRecipients <= 0) {
            return 0;
        }

        $alreadySent = $this->emailsSentThisMonth($website);
        $creditsBefore = $this->pricing->newsletterOverageCredits($alreadySent);
        $creditsAfter = $this->pricing->newsletterOverageCredits($alreadySent + $additionalRecipients);
        $toCharge = $creditsAfter - $creditsBefore;

        if ($toCharge > 0) {
            $user->spendCredits(
                $toCharge,
                'Newsletter extra emails: '.$website->name
            );
        }

        return $toCharge;
    }

    private function emailsSentThisMonth(Website $website): int
    {
        $vault = WebsiteContentVault::forWebsite($website);
        $total = 0;
        $monthStart = now()->startOfMonth();

        foreach ($vault->listNewsletters() as $newsletter) {
            if (($newsletter['status'] ?? '') !== 'sent' || blank($newsletter['sent_at'] ?? null)) {
                continue;
            }

            $sentAt = Carbon::parse($newsletter['sent_at']);
            if ($sentAt->lt($monthStart)) {
                continue;
            }

            $total += (int) ($newsletter['recipient_count'] ?? 0);
        }

        return $total;
    }

    private function activateSubscription(
        User $user,
        Website $website,
        string $type,
        $expiresAt,
        string $note,
    ): WebsiteSubscription {
        return WebsiteSubscription::create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'type' => $type,
            'status' => WebsiteSubscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'note' => $note,
        ]);
    }
}
