<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .metrics { margin: 8px 0; }
    </style>
</head>
<body>
    <h1>{{ __('messages.reports.title') }}</h1>
    <div class="meta">
        {{ $dealerName }} · {{ $periodLabel }} · {{ __('messages.reports.generated') }} {{ $generatedAt }}
    </div>

    <h2>{{ __('messages.reports.conversion_funnel') }}</h2>
    <div class="metrics">
        {{ __('messages.reports.views') }}: {{ $funnel['current']['views'] ?? 0 }} ·
        {{ __('messages.reports.enquiries') }}: {{ $funnel['current']['enquiries'] ?? 0 }} ·
        {{ __('messages.reports.leads') }}: {{ $funnel['current']['leads'] ?? 0 }} ·
        {{ __('messages.reports.won') }}: {{ $funnel['current']['won'] ?? 0 }} ·
        {{ __('messages.reports.view_to_won') }}: {{ $funnel['rates']['view_to_won'] ?? 0 }}%
    </div>

    <h2>{{ __('messages.reports.stock_metrics') }}</h2>
    <div class="metrics">
        {{ __('messages.reports.sold_rate') }}: {{ $stock['sold_rate_percent'] ?? 0 }}% ·
        {{ __('messages.reports.avg_days_on_market') }}: {{ $stock['average_days_on_market'] ?? 0 }} ·
        {{ __('messages.reports.price_drops') }}: {{ $stock['price_drops_in_period'] ?? 0 }}
    </div>

    <h2>{{ __('messages.reports.assignee_performance') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.reports.name') }}</th>
                <th>{{ __('messages.reports.leads') }}</th>
                <th>{{ __('messages.reports.win_rate') }}</th>
                <th>{{ __('messages.reports.avg_time_to_contact') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignees['assignees'] ?? [] as $row)
                <tr>
                    <td>{{ $row['name'] ?? __('messages.reports.unassigned') }}</td>
                    <td>{{ $row['total_leads'] ?? 0 }}</td>
                    <td>{{ $row['win_rate'] ?? 0 }}%</td>
                    <td>{{ $row['avg_time_to_contact_hours'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('messages.reports.no_assignee_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('messages.reports.daily_trends') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.reports.date') }}</th>
                <th>{{ __('messages.reports.views') }}</th>
                <th>{{ __('messages.reports.leads') }}</th>
                <th>{{ __('messages.reports.won') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(collect($trends['series'] ?? [])->take(-14) as $point)
                <tr>
                    <td>{{ $point['date'] ?? '' }}</td>
                    <td>{{ $point['views'] ?? 0 }}</td>
                    <td>{{ $point['leads'] ?? 0 }}</td>
                    <td>{{ $point['won'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
