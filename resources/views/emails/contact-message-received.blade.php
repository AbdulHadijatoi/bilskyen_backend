@component('emails.layouts.common', [
    'heading' => __('messages.mail.contact_message_received_heading'),
])
{{ __('messages.mail.contact_message_received_intro') }}

**{{ __('messages.mail.contact_message_received_subject_label') }}:** {{ $subjectLabel }}

**{{ __('messages.mail.contact_message_received_from') }}:** {{ $senderName }}  
**{{ __('messages.mail.contact_message_received_email') }}:** {{ $senderEmail }}

**{{ __('messages.mail.contact_message_received_message') }}:**  
{!! nl2br(e($senderMessage)) !!}
@endcomponent
