<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>{{ __('messages.mail.dealer_price_change_alert_heading', ['dealer' => $dealerName]) }}</h2>

    <p>{{ __('messages.mail.dealer_price_change_alert_intro') }}</p>

    <ul>
        @foreach($vehicles as $vehicle)
            <li>
                <strong>{{ $vehicle['title'] ?? __('messages.mail.vehicle_fallback', ['id' => $vehicle['vehicle_id']]) }}</strong>
                @if(!empty($vehicle['registration']))
                    ({{ $vehicle['registration'] }})
                @endif
                — {{ number_format((float) ($vehicle['price'] ?? 0), 0, ',', '.') }} DKK
                @if(!empty($vehicle['days_since_price_change']))
                    · {{ __('messages.mail.dealer_price_change_alert_unchanged', ['days' => $vehicle['days_since_price_change']]) }}
                @endif
            </li>
        @endforeach
    </ul>

    <p>{{ __('messages.mail.dealer_price_change_alert_footer') }}</p>
</body>
</html>
