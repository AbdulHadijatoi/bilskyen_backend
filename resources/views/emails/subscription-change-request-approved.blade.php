@component('emails.layouts.common', [
    'heading' => __('messages.mail.subscription_change_approved_heading'),
    'buttonUrl' => config('app.url'),
    'buttonText' => __('messages.mail.subscription_change_view_panel'),
])
{{ __('messages.mail.subscription_change_approved_body', ['plan' => $changeRequest->requestedPlan->name ?? '']) }}

@php
    $priceLine = \App\Support\SubscriptionMailPresenter::formatPlanPriceLine($changeRequest);
@endphp
@if($priceLine)
{{ $priceLine }}
@endif
@endcomponent
