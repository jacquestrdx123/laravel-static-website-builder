<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WebsiteNewsletter extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public string $subjectLine,
        public string $htmlBody,
        public NewsletterSubscriber $subscriber,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
        );
    }
}
