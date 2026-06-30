<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public string $vehicleTitle,
        public int $daysRemaining,
        public string $manageUrl,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.listing_expiry_reminder_subject', ['vehicle' => $this->vehicleTitle]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.listing-expiry-reminder',
        );
    }
}
