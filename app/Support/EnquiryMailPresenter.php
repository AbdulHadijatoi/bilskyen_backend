<?php

namespace App\Support;

use App\Models\Enquiry;
use App\Models\Vehicle;

/**
 * Builds Danish-facing strings for vehicle enquiry notification emails.
 */
final class EnquiryMailPresenter
{
    public function __construct(
        private readonly Vehicle $vehicle,
        private readonly Enquiry $enquiry,
    ) {}

    public static function for(Vehicle $vehicle, Enquiry $enquiry): self
    {
        return new self($vehicle, $enquiry);
    }

    public function vehicleTitle(): string
    {
        if (!empty($this->vehicle->title)) {
            return (string) $this->vehicle->title;
        }

        return __('messages.mail.vehicle_fallback', ['id' => $this->vehicle->id]);
    }

    public function typeLabel(): string
    {
        $type = (string) $this->enquiry->type;
        $key = 'messages.enquiries.types.'.$type;
        $translated = __($key);

        return $translated !== $key ? $translated : $type;
    }

    public function subjectLabel(): string
    {
        $subject = (string) $this->enquiry->subject;
        $title = $this->vehicleTitle();

        $prefixes = [
            'Enquiry about ' => 'messages.enquiries.subjects.enquiry_about',
            'Test Drive Request for ' => 'messages.enquiries.subjects.test_drive_for',
            'Price Negotiation for ' => 'messages.enquiries.subjects.price_negotiation_for',
            'Exchange request for ' => 'messages.enquiries.subjects.exchange_for',
            'Henvendelse om ' => 'messages.enquiries.subjects.enquiry_about',
            'Anmodning om prøvekørsel for ' => 'messages.enquiries.subjects.test_drive_for',
            'Prisforhandling for ' => 'messages.enquiries.subjects.price_negotiation_for',
            'Bytteanmodning for ' => 'messages.enquiries.subjects.exchange_for',
        ];

        foreach ($prefixes as $prefix => $translationKey) {
            if (str_starts_with($subject, $prefix)) {
                return __($translationKey, ['vehicle' => $title]);
            }
        }

        return $subject;
    }

    public function messageBody(): string
    {
        $message = (string) $this->enquiry->message;

        $labelMap = [
            'Licence plate:' => __('messages.enquiries.exchange_message.licence_plate').':',
            'Kilometres:' => __('messages.enquiries.exchange_message.kilometres').':',
            'Expected price (exchange vehicle):' => __('messages.enquiries.exchange_message.expected_price').':',
            'Message:' => __('messages.enquiries.exchange_message.message').':',
        ];

        return str_replace(array_keys($labelMap), array_values($labelMap), $message);
    }
}
