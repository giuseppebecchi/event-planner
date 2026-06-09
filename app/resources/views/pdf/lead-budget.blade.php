<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10mm 10mm 13mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #493729;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.32;
            background: #fffaf4;
        }
        .cover {
            border: 1px solid #ead9c8;
            background: #fffdf9;
            padding: 7mm 8mm 6mm;
            margin-bottom: 5mm;
        }
        .brand {
            margin: 0 0 6mm;
            color: #b38b68;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.28em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            color: #3b2b20;
            font-family: DejaVu Serif, serif;
            font-size: 25px;
            font-weight: 400;
            line-height: 1.1;
        }
        .subtitle {
            margin: 3mm 0 0;
            color: #8a6a52;
            font-size: 12px;
        }
        .hero-grid {
            width: 100%;
            margin-top: 5mm;
            border-collapse: collapse;
        }
        .hero-grid td {
            width: 25%;
            padding: 2.4mm 2.8mm;
            border: 1px solid #efdfd0;
            vertical-align: top;
            background: #fff7ef;
        }
        .label {
            display: block;
            margin-bottom: 1.5mm;
            color: #aa8768;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .value {
            color: #493729;
            font-size: 11px;
            font-weight: 700;
        }
        .total-strip {
            width: 100%;
            margin: 0 0 4mm;
            border-collapse: collapse;
        }
        .total-strip td {
            padding: 3mm 3.5mm;
            border: 1px solid #d8b894;
            background: #f1ddc5;
        }
        .total-title {
            color: #6c4e37;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .total-value {
            text-align: right;
            color: #2e2118;
            font-family: DejaVu Serif, serif;
            font-size: 20px;
        }
        .section {
            margin-bottom: 4.5mm;
            page-break-inside: auto;
        }
        .section-head {
            padding: 2.4mm 3mm;
            border: 1px solid #ead9c8;
            border-bottom: 0;
            background: #fffdf9;
        }
        h2 {
            margin: 0;
            color: #3b2b20;
            font-size: 13px;
            line-height: 1.2;
        }
        .section-description {
            margin: 1mm 0 0;
            color: #8a6a52;
            font-size: 10px;
        }
        table.budget {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .budget th {
            padding: 1.6mm 2mm;
            border: 1px solid #ead9c8;
            background: #f7eadb;
            color: #7f6048;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-align: left;
            text-transform: uppercase;
        }
        .budget td {
            padding: 1.45mm 2mm;
            border: 1px solid #ead9c8;
            vertical-align: top;
        }
        .budget .item {
            width: 67%;
            color: #3f2f23;
            font-weight: 700;
            white-space: pre-line;
        }
        .budget .notes {
            width: 15%;
            color: #765c47;
            white-space: pre-line;
        }
        .budget .amount {
            width: 18%;
            text-align: right;
            color: #3f2f23;
            font-weight: 700;
            white-space: nowrap;
        }
        .budget tr.total td {
            background: #f3dfc9;
            color: #3b2b20;
            font-size: 10.5px;
            font-weight: 700;
        }
        .empty {
            padding: 4mm;
            border: 1px solid #ead9c8;
            background: #fff;
            color: #8a6a52;
            font-style: italic;
        }
        .note-box {
            margin-top: 6mm;
            padding: 4mm;
            border: 1px solid #ead9c8;
            background: #fffdf9;
            color: #6f5540;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="cover">
        <p class="brand">Fairytale Italy Weddings</p>
        <h1>Budget estimate<br>{{ $summary['couple'] }}</h1>
        <p class="subtitle">Prepared for the client on {{ $summary['issued_at'] }}</p>

        <table class="hero-grid">
            <tr>
                <td><span class="label">Wedding date</span><span class="value">{{ $summary['date'] }}</span></td>
                <td><span class="label">Location</span><span class="value">{{ $summary['location'] }}</span></td>
                <td><span class="label">Guests</span><span class="value">{{ $summary['guests'] ?: 'To be confirmed' }}</span></td>
                <td><span class="label">Client budget</span><span class="value">{{ $money($summary['client_budget']) }}</span></td>
            </tr>
        </table>
    </div>

    <table class="total-strip">
        <tr>
            <td class="total-title">Estimated total budget</td>
            <td class="total-value">{{ $money($grandTotal) }}</td>
        </tr>
    </table>

    @foreach ($sections as $section)
        <section class="section">
            <div class="section-head">
                <h2>{{ $section['title'] }}</h2>
                <p class="section-description">{{ $section['description'] }}</p>
            </div>

            @if (count($section['rows']) > 0)
                <table class="budget">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Notes</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section['rows'] as $row)
                            <tr>
                                <td class="item">{{ $row['label'] ?: 'Item' }}</td>
                                <td class="notes">{{ $row['notes'] ?: '-' }}</td>
                                <td class="amount">{{ $money($row['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total">
                            <td colspan="2">Section total</td>
                            <td class="amount">{{ $money(collect($section['rows'])->sum('amount')) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="empty">No items have been added to this section yet.</div>
            @endif
        </section>
    @endforeach

    <div class="note-box">
        This budget is an estimate prepared for planning purposes. Vendor prices, availability, taxes and final scope may change until services are confirmed in writing.
    </div>
</body>
</html>
