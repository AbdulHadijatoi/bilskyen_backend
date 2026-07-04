<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>Lead SLA alert for {{ $dealerName }}</h2>

    <p>
        The following leads have not been contacted within {{ $slaHours }} hours.
        Respond promptly to protect conversion and customer experience.
    </p>

    <ul>
        @foreach($leads as $lead)
            <li>
                Lead #{{ $lead['id'] }}
                @if(!empty($lead['vehicle_title']))
                    — {{ $lead['vehicle_title'] }}
                @endif
                · waiting {{ $lead['hours_waiting'] }}h
                @if(!empty($lead['assigned_to']))
                    · assigned to {{ $lead['assigned_to'] }}
                @endif
            </li>
        @endforeach
    </ul>

    <p>Open your dealer panel to follow up on these leads.</p>
</body>
</html>
