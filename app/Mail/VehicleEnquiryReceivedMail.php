<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleEnquiryReceivedMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailLocale;

    private const PLACEHOLDER_EMAIL = 'noreply@example.com';

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
            replyTo: $this->replyToAddresses(),
        );
    }

    /**
     * @return list<Address>
     */
    private function replyToAddresses(): array
    {
        $email = trim($this->senderEmail);

        if ($email === '' || strcasecmp($email, self::PLACEHOLDER_EMAIL) === 0) {
            return [];
        }

        return [new Address($email, $this->senderName)];
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vehicle-enquiry-received',
        );
    }
}
