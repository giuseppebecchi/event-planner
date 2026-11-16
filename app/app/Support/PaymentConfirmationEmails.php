<?php

namespace App\Support;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentConfirmationEmails
{
    public function enqueue(Payment $payment): int
    {
        if ($payment->payment_status !== Payment::STATUS_PAID) {
            return 0;
        }
        $payment->loadMissing('project', 'supplier');
        $project = $payment->project;
        if (! $project) {
            return 0;
        }
        $recipients = collect([$project->email, $project->secondary_email])
            ->filter(fn ($email): bool => is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL))
            ->map(fn ($email): string => mb_strtolower(trim($email)))->unique()->values();
        $payload = [
            'couple_names' => $project->coupleNames() ?: $project->name,
            'supplier_name' => $payment->supplier?->name ?: 'the supplier',
            'amount' => 'EUR '.number_format((float) $payment->amount, 2, '.', ','),
            'reason' => $payment->reason,
            'paid_date' => $payment->paid_at?->format('F j, Y') ?: now()->format('F j, Y'),
            'project_url' => url('/admin/projects/'.$project->id),
        ];
        foreach ($recipients as $recipient) {
            DB::table('payment_confirmation_emails')->insertOrIgnore([
                'payment_id' => $payment->id, 'recipient' => $recipient,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('payment_confirmation_emails')->where('payment_id', $payment->id)->where('recipient', $recipient)
                ->whereNull('sent_at')->update(['payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
        }

        return $recipients->count();
    }
}
