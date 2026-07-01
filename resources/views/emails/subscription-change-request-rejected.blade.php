@component('emails.layouts.common', [
    'heading' => __('messages.mail.subscription_change_rejected_heading'),
])
{{ __('messages.mail.subscription_change_rejected_body', ['plan' => $changeRequest->requestedPlan->name ?? '']) }}

@if($changeRequest->rejection_reason)
**{{ __('messages.mail.subscription_change_rejection_reason') }}**  
{{ $changeRequest->rejection_reason }}
@endif
@endcomponent
