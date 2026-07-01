<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryFollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enquiry $enquiry,
        public string $stepKey,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->stepKey) {
            'day_3' => __('messages.marketing.follow_up_day3_subject'),
            'reminder' => __('messages.marketing.abandoned_enquiry_subject'),
            default => __('messages.marketing.follow_up_day1_subject'),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketing.enquiry-follow-up',
            with: [
                'enquiry' => $this->enquiry,
                'stepKey' => $this->stepKey,
            ],
        );
    }
}
