<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $vehicle = $this->lead->vehicle?->title ?? __('messages.mail.new_lead.vehicle_fallback');

        return new Envelope(
            subject: __('messages.mail.new_lead.subject', ['vehicle' => $vehicle]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-lead-notification',
            with: [
                'lead' => $this->lead,
                'recipient' => $this->recipient,
            ],
        );
    }
}
