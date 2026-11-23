<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cafe Ordering System - Sales Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #222222;
            margin: 0;
            padding: 15px;
        }
        .header {
            border-bottom: 2px solid #66402E;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #66402E;
            margin: 0 0 3px 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #555555;
            margin: 0;
        }
        .meta-info {
            text-align: right;
            font-size: 10px;
            color: #444444;
            line-height: 1.5;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .summary-box {
            border: 1px solid #dddddd;
            background-color: #f9f7f6;
            padding: 8px 12px;
            width: 33.33%;
        }
        .summary-box.highlight {
            background-color: #66402E;
            color: #ffffff;
            border-color: #66402E;
        }
        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            opacity: 0.8;
            display: block;
            margin-bottom: 2px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        table.data-table th {
            background-color: #66402E;
            color: #ffffff;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            border: 1px solid #66402E;
        }
        table.data-table td {
            padding: 7px 10px;
            border: 1px solid #e0e0e0;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .fw-bold { font-weight: bold; }
        .signature-section {
            width: 100%;
            margin-top: 35px;
        }
        .signature-box {
            width: 240px;
            margin-left: auto;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #222222;
            margin-bottom: 5px;
        }
        .signature-title {
            font-weight: bold;
            font-size: 11px;
            color: #222222;
        }
        .signature-subtitle {
            font-size: 9.5px;
            color: #666666;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">Cafe Ordering System</div>
                    <div class="report-subtitle">Sales Report &amp; Transaction Audit Trail</div>
                </td>
                <td class="meta-info">
                    <div><strong>Period:</strong> {{ $meta['dateRange'] }}</div>
                    <div><strong>Filter:</strong> {{ $meta['filterLabel'] }}</div>
                    <div><strong>Generated:</strong> {{ $meta['generatedAt'] }} by {{ $meta['generatedBy'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Totals for Exported Rows -->
    <table class="summary-table">
        <tr>
            <td class="summary-box">
                <span class="summary-label">Selected Records</span>
                <span class="summary-value">{{ $summary['count'] }}</span>
            </td>
            <td class="summary-box">
                <span class="summary-label">Total Items Sold</span>
                <span class="summary-value">{{ $summary['totalQuantity'] }}</span>
            </td>
            <td class="summary-box highlight">
                <span class="summary-label">Total Exported Sales</span>
                <span class="summary-value">₱{{ $summary['totalSales'] }}</span>
            </td>
        </tr>
    </table>

    <!-- Selected Audit Trail Rows -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Date &amp; Time</th>
                <th>Order ID</th>
                <th>Item</th>
                <th>Category</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Subtotal</th>
                <th>Payment</th>
                <th>Cashier</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $row)
                <tr>
                    <td>{{ $row->formatted_date }}</td>
                    <td class="fw-bold">#{{ str_pad($row->orderID, 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="fw-bold">{{ $row->itemname ?? 'Unknown Item' }}</td>
                    <td>{{ $row->categoryname ?? 'Uncategorized' }}</td>
                    <td class="text-center fw-bold">{{ $row->quantity }}</td>
                    <td class="text-right fw-bold">₱{{ number_format((float) $row->subtotal, 2) }}</td>
                    <td>{{ $row->paymentmethod ?? 'N/A' }}</td>
                    <td>{{ $row->cashier ?? 'System' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Single Signature Line -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-title">Authorized Signature</div>
            <div class="signature-subtitle">Prepared / Verified By</div>
        </div>
    </div>

</body>
</html>