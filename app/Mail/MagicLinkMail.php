<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $magicLinkUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.magic_link_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.magic-link',
        );
    }
}
