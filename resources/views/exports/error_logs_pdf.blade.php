<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cafe Ordering System - Error Logs Report</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #222222; margin: 0; padding: 15px; }
        .header { border-bottom: 2px solid #66402E; padding-bottom: 12px; margin-bottom: 16px; }
        .header-table, .summary-table { width: 100%; border-collapse: collapse; }
        .brand-title { font-size: 20px; font-weight: bold; color: #66402E; margin: 0 0 3px 0; }
        .report-subtitle { font-size: 12px; color: #555555; margin: 0; }
        .meta-info { text-align: right; font-size: 10px; color: #444444; line-height: 1.5; }
        .summary-table { margin-bottom: 16px; }
        .summary-box { border: 1px solid #dddddd; background-color: #f9f7f6; padding: 8px 12px; width: 25%; }
        .summary-box.highlight { background-color: #66402E; color: #ffffff; border-color: #66402E; }
        .summary-label { font-size: 8.5px; text-transform: uppercase; opacity: 0.85; display: block; margin-bottom: 2px; }
        .summary-value { font-size: 14px; font-weight: bold; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        table.data-table th { background-color: #66402E; color: #ffffff; text-align: left; padding: 7px 8px; font-size: 9.5px; border: 1px solid #66402E; }
        table.data-table td { padding: 6px 8px; border: 1px solid #e0e0e0; font-size: 9.5px; vertical-align: top; word-break: break-word; }
        table.data-table tr:nth-child(even) td { background-color: #fafafa; }
        .fw-bold { font-weight: bold; }
        .signature-section { width: 100%; margin-top: 35px; }
        .signature-box { width: 240px; margin-left: auto; text-align: center; }
        .signature-line { border-top: 1px solid #222222; margin-bottom: 5px; }
        .signature-title { font-weight: bold; font-size: 11px; color: #222222; }
        .signature-subtitle { font-size: 9.5px; color: #666666; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">{{ $systemName }}</div>
                    <div class="report-subtitle">Audit Trail &mdash; System &amp; Application Error Logs</div>
                </td>
                <td class="meta-info">
                    <div><strong>Period:</strong> {{ $meta['dateRange'] }}</div>
                    <div><strong>Filter:</strong> {{ $meta['filterLabel'] }}</div>
                    <div><strong>Generated:</strong> {{ $meta['generatedAt'] }} by {{ $meta['generatedBy'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-box highlight">
                <span class="summary-label">Exported Records</span>
                <span class="summary-value">{{ $summary['total'] }}</span>
            </td>
            <td class="summary-box">
                <span class="summary-label">Critical / Alert</span>
                <span class="summary-value">{{ $summary['critical'] }}</span>
            </td>
            <td class="summary-box">
                <span class="summary-label">Errors</span>
                <span class="summary-value">{{ $summary['error'] }}</span>
            </td>
            <td class="summary-box">
                <span class="summary-label">Warnings</span>
                <span class="summary-value">{{ $summary['warning'] }}</span>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Level</th>
                <th>Exception</th>
                <th>Error Message</th>
                <th>Location</th>
                <th>Route / URL</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $row)
                <tr>
                    <td>{{ $row->formatted_date }}</td>
                    <td class="fw-bold">{{ strtoupper($row->level) }}</td>
                    <td>{{ $row->exception ?? 'Application Log' }}</td>
                    <td class="fw-bold">{{ $row->message }}</td>
                    <td>{{ $row->file ? "{$row->file}:{$row->line}" : 'N/A' }}</td>
                    <td>{{ $row->url ?? 'N/A' }}</td>
                    <td>{{ $row->username ?? 'System / Guest' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-title">Authorized Signature</div>
            <div class="signature-subtitle">Prepared / Verified By</div>
        </div>
    </div>
</body>
</html>