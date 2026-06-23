<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Template;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'payments:send-reminders
        {--date= : Override the current date in Y-m-d format}
        {--dry-run : List matching payments without sending emails}';

    protected $description = 'Send payment reminder emails 7 days before the due date.';

    public function handle(): int
    {
        if (! Template::query()->where('slug', 'mail-reminder-payment')->exists()) {
            $this->error('Missing template slug: mail-reminder-payment');

            return self::FAILURE;
        }

        $today = $this->currentDate();

        if (! $today) {
            return self::FAILURE;
        }

        $dueDate = $today->copy()->addDays(7);
        $payments = $this->paymentsDueOn($dueDate);

        if ($payments->isEmpty()) {
            $this->info('No payment reminders to send for due date ' . $dueDate->toDateString() . '.');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($payments as $payment) {
            $recipients = $this->paymentRecipients($payment);

            if ($recipients->isEmpty()) {
                $this->warn(sprintf('Skipping payment #%d: no valid project recipient email.', $payment->id));
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '[dry-run] Payment #%d due %s to %s',
                    $payment->id,
                    $payment->due_date?->toDateString() ?? 'n/a',
                    $recipients->implode(', ')
                ));
                $skipped++;

                continue;
            }

            try {
                foreach ($recipients as $recipient) {
                    Notification::route('mail', $recipient)
                        ->notify(new PaymentReminderNotification($payment));
                }

                $payment->forceFill([
                    'payment_reminder_sent_at' => now(),
                ])->save();

                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $this->error(sprintf('Failed sending reminder for payment #%d.', $payment->id));
                $failed++;
            }
        }

        $this->info(sprintf(
            'Payment reminders completed. Sent: %d. Skipped: %d. Failed: %d.',
            $sent,
            $skipped,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function currentDate(): ?Carbon
    {
        $date = $this->option('date');

        if (blank($date)) {
            return now()->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', (string) $date)->startOfDay();
        } catch (Throwable) {
            $this->error('The --date option must use the Y-m-d format.');

            return null;
        }
    }

    protected function paymentsDueOn(Carbon $dueDate): Collection
    {
        return Payment::query()
            ->with(['project', 'supplier'])
            ->whereDate('due_date', $dueDate)
            ->where('payment_status', '!=', Payment::STATUS_PAID)
            ->whereNull('payment_reminder_sent_at')
            ->whereHas('project', fn ($query) => $query->whereIn('status', ['proposal', 'confirmed']))
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    protected function paymentRecipients(Payment $payment): Collection
    {
        return collect([
            $payment->project?->reference_email,
            $payment->project?->partner_2_reference_email,
        ])
            ->filter(fn ($email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
    }
}
