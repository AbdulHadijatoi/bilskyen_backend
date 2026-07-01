<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleEnquiryReceivedMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public string $vehicleTitle,
        public string $vehicleUrl,
        public string $enquiryType,
        public string $enquirySubject,
        public string $senderName,
        public string $senderEmail,
        public ?string $senderPhone,
        public string $senderMessage,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.mail.vehicle_enquiry_received_subject', ['vehicle' => $this->vehicleTitle]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vehicle-enquiry-received',
        );
    }
}
