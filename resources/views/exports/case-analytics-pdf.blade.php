<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Case Analytics Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #051823;
            color: #ffffff;
            padding: 14px 18px;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 21px;
            margin: 0;
            font-weight: 700;
        }

        .header p {
            margin: 4px 0 0;
            font-size: 9px;
            color: #dbeafe;
        }

        .meta {
            margin-bottom: 12px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 9px;
            color: #475569;
        }

        .metric-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 12px;
        }

        .metric-card {
            width: 20%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px;
            background: #ffffff;
        }

        .metric-title {
            font-size: 8px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 15px;
            font-weight: 700;
            color: #051823;
            margin-bottom: 4px;
        }

        .metric-change {
            font-size: 8px;
            color: #475569;
        }

        h2 {
            font-size: 13px;
            margin: 14px 0 7px;
            color: #051823;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }

        th {
            background: #d5e0e7;
            color: #051823;
            font-weight: 700;
            text-align: left;
            border: 1px solid #b8c8d3;
            padding: 7px;
            font-size: 8px;
        }

        td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            vertical-align: top;
            font-size: 8px;
            line-height: 1.3;
            word-wrap: break-word;
        }

        tbody tr:nth-child(even) {
            background: #eef3f7;
        }

        .two-column {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }

        .two-column td {
            border: 0;
            padding: 0;
            width: 50%;
            vertical-align: top;
        }
    </style>
</head>
<body>
    @php
        $selected = $payload['filters']['selected'] ?? [];
        $metrics = $payload['metrics'] ?? [];
    @endphp

    <div class="header">
        <h1>Case Analytics Report</h1>
        <p>Generated at {{ $generatedAt }}</p>
    </div>

    <div class="meta">
        <strong>Date Range:</strong> {{ $selected['date_range_label'] ?? '-' }}
        &nbsp;|&nbsp;
        <strong>Time Range:</strong> {{ $selected['time_range_label'] ?? '-' }}
        &nbsp;|&nbsp;
        <strong>Team:</strong> {{ $selected['team_label'] ?? '-' }}
    </div>

    <table class="metric-grid">
        <tr>
            @foreach ($metrics as $metric)
                <td class="metric-card">
                    <div class="metric-title">{{ $metric['title'] ?? '-' }}</div>
                    <div class="metric-value">{{ $metric['value_display'] ?? '-' }}</div>
                    <div class="metric-change">
                        Prev: {{ $metric['previous_value_display'] ?? '-' }}
                        | Change: {{ $metric['change_pct'] ?? 0 }}%
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="two-column">
        <tr>
            <td>
                <h2>Top Categories</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Tickets</th>
                            <th>Top Team</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topCategoryRows as $row)
                            <tr>
                                <td>{{ $row[0] }}</td>
                                <td>{{ $row[1] }}</td>
                                <td>{{ $row[2] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            <td>
                <h2>Top Issues</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Issue Type</th>
                            <th>Category</th>
                            <th>Tickets</th>
                            <th>Top Team</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topIssueRows as $row)
                            <tr>
                                <td>{{ $row[0] }}</td>
                                <td>{{ $row[1] }}</td>
                                <td>{{ $row[2] }}</td>
                                <td>{{ $row[3] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <h2>Monthly Trend</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Incoming</th>
                <th>Resolved</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trendRows as $row)
                <tr>
                    <td>{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No data available</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="two-column">
        <tr>
            <td>
                <h2>Team Performance</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Agent</th>
                            <th>Resolved</th>
                            <th>Avg. Time</th>
                            <th>CSAT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaderboardRows as $row)
                            <tr>
                                <td>{{ $row[0] }}</td>
                                <td>{{ $row[1] }}</td>
                                <td>{{ $row[2] }}</td>
                                <td>{{ $row[3] }}</td>
                                <td>{{ $row[4] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            <td>
                <h2>Peak Time Ticket Volume</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Hour</th>
                            <th>Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peakTimeRows as $row)
                            <tr>
                                <td>{{ $row[0] }}</td>
                                <td>{{ $row[1] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
