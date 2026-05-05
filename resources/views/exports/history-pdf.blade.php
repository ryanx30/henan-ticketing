<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket History Export</title>

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
            font-size: 20px;
            margin: 0;
            font-weight: 700;
        }

        .meta {
            margin: 0 0 12px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 9px;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background: #d5e0e7;
            color: #051823;
            font-weight: 700;
            text-align: left;
            border: 1px solid #b8c8d3;
            padding: 8px 7px;
            font-size: 9px;
        }

        td {
            border: 1px solid #cbd5e1;
            padding: 7px;
            vertical-align: top;
            font-size: 9px;
            line-height: 1.35;
            word-wrap: break-word;
        }

        tbody tr:nth-child(even) {
            background: #eef3f7;
        }

        .ticket {
            width: 15%;
        }

        .resolved {
            width: 15%;
        }

        .category {
            width: 15%;
        }

        .team {
            width: 12%;
        }

        .note {
            width: 28%;
        }

        .duration {
            width: 15%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ticket History Repository</h1>
    </div>

    <div class="meta">
        Search:
        {{ $filters['q'] ?: '-' }}
        &nbsp;|&nbsp;

        Date:
        {{ $filters['date_from'] ?: '-' }}
        →
        {{ $filters['date_to'] ?: '-' }}
        &nbsp;|&nbsp;

        Sort:
        {{ $filters['sort_by'] }}
        ({{ strtoupper($filters['sort_dir']) }})
        &nbsp;|&nbsp;

        Generated:
        {{ now()->format('d M Y, H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="ticket">Ticket</th>
                <th class="resolved">Resolved Date</th>
                <th class="category">Category</th>
                <th class="team">Team</th>
                <th class="note">Resolution Note</th>
                <th class="duration">Duration (SLA)</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                    <td>{{ $row[3] }}</td>
                    <td>{{ $row[4] }}</td>
                    <td>{{ $row[5] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 16px;">
                        No history found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>