<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectPaymentsPdfController extends Controller
{
    public function __invoke(Project $project)
    {
        $payments = $project
            ->payments()
            ->with(['supplier', 'categoryBudgetSupplier.category'])
            ->get()
            ->sortBy(fn (Payment $payment): string => sprintf(
                '%s-%05d',
                $payment->due_date?->format('Ymd') ?? '99999999',
                (int) $payment->id,
            ))
            ->values();

        $pdf = Pdf::loadView('pdf.project-payments', [
            'project' => $project,
            'payments' => $payments,
            'totalAmount' => (float) $payments->sum(fn (Payment $payment): float => (float) $payment->amount),
            'paidAmount' => (float) $payments
                ->where('payment_status', Payment::STATUS_PAID)
                ->sum(fn (Payment $payment): float => (float) $payment->amount),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(sprintf(
            '%s-payments.pdf',
            str($project->name)->slug()->value() ?: 'project'
        ));
    }
}
