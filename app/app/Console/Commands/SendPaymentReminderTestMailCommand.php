<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Template;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendPaymentReminderTestMailCommand extends Command
{
    protected $signature = 'payments:send-reminder-test
        {payment? : Payment ID to use for the reminder}
        {--to= : Override MAIL_TEST_TO for this run}';

    protected $description = 'Send a payment reminder test email to the configured test recipient.';

    public function handle(): int
    {
        if (! Template::query()->where('slug', 'mail-reminder-payment')->exists()) {
            $this->error('Missing template slug: mail-reminder-payment');

            return self::FAILURE;
        }

        $recipient = trim((string) ($this->option('to') ?: config('mail.test_to')));

        $validator = Validator::make(
            ['email' => $recipient],
            ['email' => ['required', 'email']]
        );

        if ($validator->fails()) {
            $this->error('Set a valid MAIL_TEST_TO email address, or pass --to=test@example.com.');

            return self::FAILURE;
        }

        $payment = $this->payment();

        if (! $payment) {
            $this->error('No payment found. Create a payment first or pass a valid payment ID.');

            return self::FAILURE;
        }

        Notification::route('mail', $recipient)
            ->notify(new PaymentReminderNotification($payment));

        $this->info(sprintf(
            'Payment reminder test email sent to %s using payment #%d.',
            $recipient,
            $payment->id,
        ));

        return self::SUCCESS;
    }

    protected function payment(): ?Payment
    {
        $paymentId = $this->argument('payment');

        if (filled($paymentId)) {
            return Payment::query()
                ->with(['project', 'supplier'])
                ->find((int) $paymentId);
        }

        return Payment::query()
            ->with(['project', 'supplier'])
            ->whereNotNull('due_date')
            ->where('payment_status', '!=', Payment::STATUS_PAID)
            ->orderBy('due_date')
            ->orderBy('id')
            ->first();
    }
}
