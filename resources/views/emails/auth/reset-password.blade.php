@component('emails.layouts.common', [
    'heading' => __('messages.mail.password_reset_heading'),
    'buttonUrl' => $resetUrl,
    'buttonText' => __('messages.mail.password_reset_button'),
])
{{ __('messages.mail.password_reset_body') }}
@endcomponent
