<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>Stale pricing alert for {{ $dealerName }}</h2>

    <p>
        The following published listings have not had a price change in at least 14 days.
        Market conditions may have shifted — review pricing to stay competitive.
    </p>

    <ul>
        @foreach($vehicles as $vehicle)
            <li>
                <strong>{{ $vehicle['title'] ?? ('Vehicle #'.$vehicle['vehicle_id']) }}</strong>
                @if(!empty($vehicle['registration']))
                    ({{ $vehicle['registration'] }})
                @endif
                — {{ number_format((float) ($vehicle['price'] ?? 0), 0, ',', '.') }} DKK
                @if(!empty($vehicle['days_since_price_change']))
                    · unchanged for {{ $vehicle['days_since_price_change'] }} days
                @endif
            </li>
        @endforeach
    </ul>

    <p>Open your dealer dashboard to adjust prices or apply suggested market pricing.</p>
</body>
</html>
