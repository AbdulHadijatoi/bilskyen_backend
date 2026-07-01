@component('emails.layouts.common', [
    'heading' => __('messages.mail.magic_link_heading'),
    'buttonUrl' => $magicLinkUrl,
    'buttonText' => __('messages.mail.magic_link_button'),
])
{{ __('messages.mail.magic_link_body') }}
@endcomponent
