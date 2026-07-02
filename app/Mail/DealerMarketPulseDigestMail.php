<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealerMarketPulseDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $summaries
     */
    public function __construct(
        public string $dealerName,
        public array $summaries,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your weekly Bilskyen Market Pulse',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.dealer-market-pulse-digest', [
                'dealerName' => $this->dealerName,
                'summaries' => $this->summaries,
            ])->render(),
        );
    }
}
