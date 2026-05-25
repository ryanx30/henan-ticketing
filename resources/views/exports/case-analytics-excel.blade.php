<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Case Analytics Report</title>
</head>
<body>
@php
    $selected = data_get($payload, 'filters.selected', []);

    $reportRange = data_get($selected, 'date_range_label')
        ?? data_get($selected, 'time_range_label')
        ?? data_get($payload, 'range_label')
        ?? '-';

    $timeRangeLabel = data_get($selected, 'time_range_label')
        ?? data_get($selected, 'time_range')
        ?? '-';

    $teamLabel = data_get($selected, 'team_label')
        ?? strtoupper((string) (data_get($selected, 'team') ?? 'ALL'));

    $generatedAtLabel = $generatedAt ?? now()->format('d M Y, H:i:s');

    $cell = function ($row, $index, $fallback = '-') {
        return data_get($row, $index, $fallback);
    };
@endphp

<table border="0" cellspacing="0" cellpadding="0">
    {{-- TITLE --}}
    <tr>
        <td colspan="8" style="font-size:18px; font-weight:bold; background:#1F4E78; color:#FFFFFF; padding:10px; text-align:center;">
            CASE ANALYTICS REPORT
        </td>
    </tr>

    <tr><td colspan="8"></td></tr>

    {{-- META --}}
    <tr>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px;">Date Range</td>
        <td colspan="6" style="padding:6px;">{{ $reportRange }}</td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px;">Time Range</td>
        <td colspan="6" style="padding:6px;">{{ $timeRangeLabel }}</td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px;">Team</td>
        <td colspan="6" style="padding:6px;">{{ $teamLabel }}</td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px;">Generated At</td>
        <td colspan="6" style="padding:6px;">{{ $generatedAtLabel }}</td>
    </tr>

    <tr><td colspan="8"></td></tr>
    <tr><td colspan="8"></td></tr>

    {{-- SUMMARY KPI --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Summary KPI
        </td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Metric</td>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Current</td>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Previous</td>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Change</td>
        <td colspan="3" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Trend</td>
    </tr>
    @forelse($metricRows as $row)
        <tr>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1) }}</td>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 2) }}</td>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 3) }}</td>
            <td colspan="3" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 4) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>

    {{-- TOP CATEGORIES --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Top Categories
        </td>
    </tr>
    <tr>
        <td colspan="3" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Category</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Tickets</td>
        <td colspan="3" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Team with Most Tickets</td>
    </tr>
    @forelse($topCategoryRows as $row)
        <tr>
            <td colspan="3" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1, 0) }}</td>
            <td colspan="3" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>

    {{-- TOP ISSUES --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Top Issues
        </td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Issue Type</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Category</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Tickets</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Team with Most Tickets</td>
    </tr>
    @forelse($topIssueRows as $row)
        <tr>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 2, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 3) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>

    {{-- MONTHLY TREND --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Monthly Trend
        </td>
    </tr>
    <tr>
        <td colspan="3" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Month</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Incoming</td>
        <td colspan="3" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Resolved</td>
    </tr>
    @forelse($trendRows as $row)
        <tr>
            <td colspan="3" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1, 0) }}</td>
            <td colspan="3" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 2, 0) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>

    {{-- TEAM PERFORMANCE --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Team Performance
        </td>
    </tr>
    <tr>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Rank</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Agent</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Resolved</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Avg. Resolution</td>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">CSAT</td>
    </tr>
    @forelse($leaderboardRows as $row)
        <tr>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 2, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 3) }}</td>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 4) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>


    {{-- TOP TEAMS --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Top Teams
        </td>
    </tr>
    <tr>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Rank</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Team</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Tickets</td>
        <td style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Resolved</td>
        <td colspan="2" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Avg. Resolution</td>
    </tr>
    @forelse($topTeamRows ?? [] as $row)
        <tr>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 2, 0) }}</td>
            <td style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 3, 0) }}</td>
            <td colspan="2" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 4) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>

    {{-- PEAK TIME --}}
    <tr>
        <td colspan="8" style="font-size:16px; font-weight:bold; background:#5B9BD5; color:#FFFFFF; padding:8px;">
            Peak Time Ticket Volume
        </td>
    </tr>
    <tr>
        <td colspan="4" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Hour</td>
        <td colspan="4" style="font-weight:bold; background:#D9EAF7; padding:6px; border:1px solid #A6A6A6;">Tickets</td>
    </tr>
    @forelse($peakTimeRows as $row)
        <tr>
            <td colspan="4" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 0) }}</td>
            <td colspan="4" style="padding:6px; border:1px solid #D0D7DE;">{{ $cell($row, 1, 0) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="padding:6px; border:1px solid #D0D7DE;">No data available</td>
        </tr>
    @endforelse
</table>
</body>
</html>