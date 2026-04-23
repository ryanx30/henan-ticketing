<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket History Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        .meta { margin-bottom: 12px; font-size: 10px; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; }
    </style>
</head>
<body>
    <h1>Ticket History Export</h1>

    <div class="meta">
        Search: {{ $filters['q'] ?: '-' }} |
        Date: {{ $filters['date_from'] ?: '-' }} → {{ $filters['date_to'] ?: '-' }} |
        Sort: {{ $filters['sort_by'] }} ({{ $filters['sort_dir'] }})
    </div>

    <table>
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Resolved Date</th>
                <th>Category</th>
                <th>Team</th>
                <th>Resolution Note</th>
                <th>Duration</th>
                <th>SLA</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tickets as $t)
                <tr>
                    <td>{{ $helper->ticketLabel($t) }}</td>
                    <td>{{ $helper->resolvedLabel($t) }}</td>
                    <td>{{ $helper->categoryLabel($t) }}</td>
                    <td>{{ strtoupper($t->team ?? '-') }}</td>
                    <td>{{ $helper->resolutionLabel($t) }}</td>
                    <td>{{ $helper->durationText($t) }}</td>
                    <td>{{ $helper->slaBadge($t) ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>