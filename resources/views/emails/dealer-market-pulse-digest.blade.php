<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>{{ __('messages.mail.dealer_market_pulse_digest_heading', ['dealer' => $dealerName]) }}</h2>

    @if(!empty($portfolio['avg_score']))
        <p>
            {{ __('messages.mail.dealer_market_pulse_digest_portfolio_health', ['score' => $portfolio['avg_score']]) }}
            @if(!empty($portfolio['platform_avg_score']))
                ({{ __('messages.mail.dealer_market_pulse_digest_platform_avg', ['avg' => $portfolio['platform_avg_score']]) }})
            @endif
            @if(isset($portfolio['attention_count']) && $portfolio['attention_count'] > 0)
                — {{ __('messages.mail.dealer_market_pulse_digest_listings_need_attention', ['count' => $portfolio['attention_count']]) }}
            @endif
        </p>
    @endif

    @if(!empty($attentionSummaries))
        <h3>{{ __('messages.mail.dealer_market_pulse_digest_attention_heading') }}</h3>
        <ul>
            @foreach($attentionSummaries as $summary)
                <li>{{ $summary }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($aiBriefing))
        <h3>{{ __('messages.mail.dealer_market_pulse_digest_ai_heading') }}</h3>
        <p style="white-space: pre-line;">{{ $aiBriefing }}</p>
    @endif

    @if(!empty($summaries))
        <h3>{{ __('messages.mail.dealer_market_pulse_digest_market_pulse_heading') }}</h3>
        <p>{{ __('messages.mail.dealer_market_pulse_digest_market_pulse_intro') }}</p>
        <ul>
            @foreach($summaries as $summary)
                <li>{{ $summary }}</li>
            @endforeach
        </ul>
    @endif

    <p>{{ __('messages.mail.dealer_market_pulse_digest_footer') }}</p>
</body>
</html>
