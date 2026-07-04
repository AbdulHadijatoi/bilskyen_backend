<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealerCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $campaignSubject,
        public string $campaignBody,
        public string $campaignName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaignSubject ?: $this->campaignName);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketing.dealer-campaign',
            with: [
                'body' => $this->campaignBody,
                'campaignName' => $this->campaignName,
            ],
        );
    }
}
