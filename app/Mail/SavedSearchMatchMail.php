<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SavedSearchMatchMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public int $matchCount,
        public string $vehiclesUrl,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.saved_search_match_subject', ['count' => $this->matchCount]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.saved-search-match',
        );
    }
}
