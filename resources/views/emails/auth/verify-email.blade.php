@component('emails.layouts.common', [
    'heading' => __('messages.mail.verify_email_heading'),
    'buttonUrl' => $verificationUrl,
    'buttonText' => __('messages.mail.verify_email_button'),
])
{{ __('messages.mail.verify_email_body') }}
@endcomponent
