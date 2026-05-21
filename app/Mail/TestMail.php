<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public string $bodyLine
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.test_subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.test',
        );
    }
}
