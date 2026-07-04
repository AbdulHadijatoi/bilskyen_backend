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
     * @param  array<int, string>  $attentionSummaries
     * @param  array<string, mixed>  $portfolio
     */
    public function __construct(
        public string $dealerName,
        public array $summaries,
        public array $attentionSummaries = [],
        public array $portfolio = [],
        public ?string $aiBriefing = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your weekly Bilskyen dealer briefing',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.dealer-market-pulse-digest', [
                'dealerName' => $this->dealerName,
                'summaries' => $this->summaries,
                'attentionSummaries' => $this->attentionSummaries,
                'portfolio' => $this->portfolio,
                'aiBriefing' => $this->aiBriefing,
            ])->render(),
        );
    }
}
