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
    <h1>Dealer analytics report</h1>
    <div class="meta">
        {{ $dealerName }} · {{ $periodLabel }} · Generated {{ $generatedAt }}
    </div>

    <h2>Conversion funnel</h2>
    <div class="metrics">
        Views: {{ $funnel['current']['views'] ?? 0 }} ·
        Enquiries: {{ $funnel['current']['enquiries'] ?? 0 }} ·
        Leads: {{ $funnel['current']['leads'] ?? 0 }} ·
        Won: {{ $funnel['current']['won'] ?? 0 }} ·
        View → won: {{ $funnel['rates']['view_to_won'] ?? 0 }}%
    </div>

    <h2>Stock metrics</h2>
    <div class="metrics">
        Sold rate: {{ $stock['sold_rate_percent'] ?? 0 }}% ·
        Avg days on market: {{ $stock['average_days_on_market'] ?? 0 }} ·
        Price drops: {{ $stock['price_drops_in_period'] ?? 0 }}
    </div>

    <h2>Assignee performance</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Leads</th>
                <th>Win rate</th>
                <th>Avg time to contact (h)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignees['assignees'] ?? [] as $row)
                <tr>
                    <td>{{ $row['name'] ?? 'Unassigned' }}</td>
                    <td>{{ $row['total_leads'] ?? 0 }}</td>
                    <td>{{ $row['win_rate'] ?? 0 }}%</td>
                    <td>{{ $row['avg_time_to_contact_hours'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No assignee data</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Daily trends (last rows)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Views</th>
                <th>Leads</th>
                <th>Won</th>
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
