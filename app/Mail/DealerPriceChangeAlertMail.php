<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealerPriceChangeAlertMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    /**
     * @param  array<int, array<string, mixed>>  $vehicles
     */
    public function __construct(
        public string $dealerName,
        public array $vehicles,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.dealer_price_change_alert_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.dealer-price-change-alert', [
                'dealerName' => $this->dealerName,
                'vehicles' => $this->vehicles,
            ])->render(),
        );
    }
}
