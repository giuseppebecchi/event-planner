<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendPaymentConfirmationsCommand extends Command
{
    protected $signature = 'payments:send-confirmations {--retry-failed : Retry unsent confirmations that exhausted automatic attempts}';

    protected $description = 'Send pending English payment receipt confirmations to couples.';

    public function handle(): int
    {
        if ($this->option('retry-failed')) {
            DB::table('payment_confirmation_emails')->whereNull('sent_at')->where('attempts', '>=', 5)->update(['attempts' => 0]);
        }
        $ids = DB::table('payment_confirmation_emails')->whereNull('sent_at')->where('attempts', '<', 5)->orderBy('id')->limit(100)->pluck('id');
        $failed = false;
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$failed): void {
                $row = DB::table('payment_confirmation_emails')->where('id', $id)->lockForUpdate()->first();
                if (! $row || $row->sent_at || $row->attempts >= 5) {
                    return;
                }
                $payment = Payment::query()->with('project')->find($row->payment_id);
                if (! $payment?->project || $payment->payment_status !== Payment::STATUS_PAID) {
                    return;
                }
                DB::table('payment_confirmation_emails')->where('id', $id)->update(['attempts' => $row->attempts + 1, 'last_attempt_at' => now(), 'updated_at' => now()]);
                try {
                    Notification::route('mail', $row->recipient)->notify(new PaymentReceivedNotification(json_decode($row->payload, true, flags: JSON_THROW_ON_ERROR)));
                    DB::table('payment_confirmation_emails')->where('id', $id)->update(['sent_at' => now(), 'updated_at' => now()]);
                    $this->info('Confirmation sent for payment #'.$row->payment_id.'.');
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error('Confirmation failed for payment #'.$row->payment_id.'; the payment remains saved.');
                    $failed = true;
                }
            });
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
