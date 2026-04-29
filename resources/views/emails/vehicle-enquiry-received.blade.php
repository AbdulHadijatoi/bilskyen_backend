@component('emails.layouts.common', [
    'heading' => __('messages.mail.vehicle_enquiry_received_heading'),
    'buttonUrl' => $vehicleUrl,
    'buttonText' => __('messages.mail.vehicle_enquiry_received_button'),
])
{{ __('messages.mail.vehicle_enquiry_received_intro', ['vehicle' => $vehicleTitle]) }}

**{{ __('messages.mail.vehicle_enquiry_received_type') }}:** {{ $enquiryType }}  
**{{ __('messages.mail.vehicle_enquiry_received_subject_label') }}:** {{ $enquirySubject }}

**{{ __('messages.mail.vehicle_enquiry_received_from') }}:** {{ $senderName }}  
**{{ __('messages.mail.vehicle_enquiry_received_email') }}:** {{ $senderEmail }}  
@if($senderPhone)
**{{ __('messages.mail.vehicle_enquiry_received_phone') }}:** {{ $senderPhone }}
@endif

**{{ __('messages.mail.vehicle_enquiry_received_message') }}:**  
{!! nl2br(e($senderMessage)) !!}
@endcomponent
