<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>Weekly briefing for {{ $dealerName }}</h2>

    @if(!empty($portfolio['avg_score']))
        <p>
            Portfolio health: <strong>{{ $portfolio['avg_score'] }}/100</strong>
            @if(!empty($portfolio['platform_avg_score']))
                (Bilskyen average: {{ $portfolio['platform_avg_score'] }})
            @endif
            @if(isset($portfolio['attention_count']) && $portfolio['attention_count'] > 0)
                — {{ $portfolio['attention_count'] }} listing(s) need attention
            @endif
        </p>
    @endif

    @if(!empty($attentionSummaries))
        <h3>Listings needing attention</h3>
        <ul>
            @foreach($attentionSummaries as $summary)
                <li>{{ $summary }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($aiBriefing))
        <h3>AI action plan</h3>
        <p style="white-space: pre-line;">{{ $aiBriefing }}</p>
    @endif

    @if(!empty($summaries))
        <h3>Market Pulse</h3>
        <p>How your dealership compared to the Bilskyen market this week:</p>
        <ul>
            @foreach($summaries as $summary)
                <li>{{ $summary }}</li>
            @endforeach
        </ul>
    @endif

    <p>Open your dealer dashboard to fix listings and view full analytics.</p>
</body>
</html>
