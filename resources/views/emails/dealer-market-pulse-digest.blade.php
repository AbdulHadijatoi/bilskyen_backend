<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>Market Pulse for {{ $dealerName }}</h2>
    <p>Here is how your dealership compared to the Bilskyen market this week:</p>
    <ul>
        @foreach($summaries as $summary)
            <li>{{ $summary }}</li>
        @endforeach
    </ul>
    <p>Open your dealer dashboard for full analytics.</p>
</body>
</html>
