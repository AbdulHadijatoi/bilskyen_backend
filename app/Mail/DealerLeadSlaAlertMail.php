<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealerLeadSlaAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $leads
     */
    public function __construct(
        public string $dealerName,
        public array $leads,
        public int $slaHours = 24,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lead SLA alert — contacts overdue',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.dealer-lead-sla-alert', [
                'dealerName' => $this->dealerName,
                'leads' => $this->leads,
                'slaHours' => $this->slaHours,
            ])->render(),
        );
    }
}
