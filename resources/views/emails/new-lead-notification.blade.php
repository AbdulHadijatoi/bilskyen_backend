<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.mail.new_lead.subject', ['vehicle' => $lead->vehicle?->title ?? '']) }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111;">
    <h2>{{ __('messages.mail.new_lead.heading') }}</h2>
    <p>{{ __('messages.mail.new_lead.greeting', ['name' => $recipient->name]) }}</p>
    <p>{{ __('messages.mail.new_lead.body') }}</p>
    <ul>
        <li><strong>{{ __('messages.mail.new_lead.vehicle') }}:</strong> {{ $lead->vehicle?->title ?? '-' }}</li>
        <li><strong>{{ __('messages.mail.new_lead.stage') }}:</strong> {{ $lead->leadStage?->name ?? '-' }}</li>
        <li><strong>{{ __('messages.mail.new_lead.buyer') }}:</strong> {{ $lead->buyerUser?->name ?? __('messages.mail.new_lead.guest') }}</li>
    </ul>
    <p>{{ __('messages.mail.new_lead.footer') }}</p>
</body>
</html>
