<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} payments</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2d2a26; font-size: 11px; }
        h1 { margin: 0; font-size: 24px; }
        .meta { margin-top: 6px; color: #746d66; }
        .summary { margin: 22px 0; width: 100%; border-collapse: collapse; }
        .summary td { width: 33.33%; padding: 10px; border: 1px solid #e4ddd5; background: #fbf8f4; }
        .label { color: #746d66; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; }
        .value { margin-top: 4px; font-size: 15px; font-weight: bold; }
        table.payments { width: 100%; border-collapse: collapse; }
        table.payments th { padding: 8px 6px; border-bottom: 2px solid #2e4a62; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; }
        table.payments td { padding: 8px 6px; border-bottom: 1px solid #e8e0d7; vertical-align: top; }
        .amount { text-align: right; white-space: nowrap; }
        .status { font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .overdue { color: #9b2f2f; }
        .paid { color: #4c6d4e; }
    </style>
</head>
<body>
    <h1>{{ $project->name }}</h1>
    <div class="meta">Payments recap generated {{ $generatedAt->format('d/m/Y H:i') }}</div>

    <table class="summary">
        <tr>
            <td><div class="label">Payments</div><div class="value">{{ $payments->count() }}</div></td>
            <td><div class="label">Total</div><div class="value">EUR {{ number_format($totalAmount, 2, ',', '.') }}</div></td>
            <td><div class="label">Paid</div><div class="value">EUR {{ number_format($paidAmount, 2, ',', '.') }}</div></td>
        </tr>
    </table>

    <table class="payments">
        <thead>
            <tr>
                <th>Due date</th>
                <th>Supplier</th>
                <th>Reason</th>
                <th>Status</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $payment)
                @php
                    $isPaid = $payment->payment_status === \App\Models\Payment::STATUS_PAID;
                    $isOverdue = ! $isPaid && $payment->due_date && $payment->due_date->copy()->startOfDay()->lt(now()->startOfDay());
                @endphp
                <tr>
                    <td>{{ $payment->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                        {{ $payment->supplier?->name ?? '-' }}
                        @if ($payment->categoryBudgetSupplier?->category)
                            <br><span class="meta">{{ $payment->categoryBudgetSupplier->category->label }}</span>
                        @endif
                    </td>
                    <td>{{ $payment->reason ?: 'Payment' }}</td>
                    <td class="status {{ $isPaid ? 'paid' : ($isOverdue ? 'overdue' : '') }}">
                        {{ $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : 'Unpaid') }}
                    </td>
                    <td class="amount">EUR {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
