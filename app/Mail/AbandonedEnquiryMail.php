<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedEnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $meta,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('messages.marketing.abandoned_enquiry_subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketing.abandoned-enquiry',
            with: ['meta' => $this->meta],
        );
    }
}
