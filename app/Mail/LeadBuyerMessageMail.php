<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadBuyerMessageMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public string $vehicleTitle,
        public string $dealerName,
        public string $messageBody,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.lead_buyer_message_subject', ['vehicle' => $this->vehicleTitle]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.lead-buyer-message',
        );
    }
}
