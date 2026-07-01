<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use App\Models\DealerSubscriptionChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionChangeRequestRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public DealerSubscriptionChangeRequest $changeRequest
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.subscription_change_rejected_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-change-request-rejected',
        );
    }
}
