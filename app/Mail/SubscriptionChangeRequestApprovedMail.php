<?php

namespace App\Mail;

use App\Models\DealerSubscriptionChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionChangeRequestApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DealerSubscriptionChangeRequest $changeRequest
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.subscription_change_approved_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-change-request-approved',
        );
    }
}
