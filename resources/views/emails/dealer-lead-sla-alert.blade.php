<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>{{ __('messages.mail.dealer_lead_sla_alert_heading', ['dealer' => $dealerName]) }}</h2>

    <p>{{ __('messages.mail.dealer_lead_sla_alert_intro', ['hours' => $slaHours]) }}</p>

    <ul>
        @foreach($leads as $lead)
            <li>
                {{ __('messages.mail.dealer_lead_sla_alert_lead_id', ['id' => $lead['id']]) }}
                @if(!empty($lead['vehicle_title']))
                    — {{ $lead['vehicle_title'] }}
                @endif
                · {{ __('messages.mail.dealer_lead_sla_alert_waiting', ['hours' => $lead['hours_waiting']]) }}
                @if(!empty($lead['assigned_to']))
                    · {{ __('messages.mail.dealer_lead_sla_alert_assigned_to', ['name' => $lead['assigned_to']]) }}
                @endif
            </li>
        @endforeach
    </ul>

    <p>{{ __('messages.mail.dealer_lead_sla_alert_footer') }}</p>
</body>
</html>
