<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111;
            font-family: DejaVu Serif, serif;
            font-size: 12px;
            background: #fff;
        }
        h1 {
            margin: 0 0 8mm;
            font-size: 24px;
            line-height: 1.1;
        }
        .meta {
            margin: -5mm 0 8mm;
            color: #4f574b;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #e5efdd;
        }
        th,
        td {
            border: 1px solid #1c2a18;
            padding: 2.2mm 2mm;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #c6ddb8;
            font-size: 16px;
            font-weight: 700;
        }
        th:first-child,
        td:first-child {
            width: 48%;
            text-align: left;
        }
        .total td {
            background: #c6ddb8;
            font-size: 15px;
            font-weight: 700;
        }
        .note td {
            height: 10mm;
            background: #f8faf7;
            font-size: 11px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>{{ $budget->category?->label_it ?? 'Quote comparison' }}</h1>
    <div class="meta">
        {{ $project->name }} · {{ now()->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ $budget->category?->label_it ?? 'Item' }}</th>
                @foreach ($proposals as $proposal)
                    <th>{{ $proposal->supplier?->name ?? 'Supplier' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    @foreach ($proposals as $proposal)
                        <td>{{ $money($row['amounts'][$proposal->id] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="total">
                <td>Quote total</td>
                @foreach ($proposals as $proposal)
                    <td>{{ $money($proposal->proposed_amount) }}</td>
                @endforeach
            </tr>
            <tr class="note">
                <td>Note</td>
                @foreach ($proposals as $proposal)
                    <td>{{ $proposal->notes ?: $proposal->costs_and_conditions }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</body>
</html>
