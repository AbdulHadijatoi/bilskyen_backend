<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PriceDropAlertMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public string $vehicleTitle,
        public string $vehicleUrl,
        public float $newPrice,
        public string $currency,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.price_drop_alert_subject', ['vehicle' => $this->vehicleTitle]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.price-drop-alert',
        );
    }
}
