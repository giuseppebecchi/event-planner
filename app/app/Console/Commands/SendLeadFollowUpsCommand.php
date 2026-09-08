<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Template;
use App\Notifications\LeadFollowUpNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendLeadFollowUpsCommand extends Command
{
    protected $signature = 'leads:send-follow-ups
        {--date= : Override the current date in Y-m-d format}
        {--dry-run : Preview matching leads without sending emails}';

    protected $description = 'Send automatic follow-up emails to enabled leads after their configured waiting period.';

    public function handle(): int
    {
        if (! Template::query()->where('slug', 'mail-lead-follow-up')->where('language', 'en')->exists()) {
            $this->error('Missing template slug: mail-lead-follow-up');

            return self::FAILURE;
        }

        $today = $this->currentDate();

        if (! $today) {
            return self::FAILURE;
        }

        $leads = $this->leadsDueForFollowUp($today);

        if ($leads->isEmpty()) {
            $this->info('No automatic lead follow ups to send.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf('Automatic lead follow ups preview for %s:', $today->toDateString()));
            $this->table(
                ['Lead', 'Couple', 'Base date', 'Days', 'Due since', 'Recipients'],
                $leads->map(fn (Lead $lead): array => [
                    $lead->id,
                    $lead->couple_name ?: $lead->email ?: 'Lead',
                    $this->followUpBaseDate($lead)?->toDateString() ?? 'n/a',
                    (int) $lead->follow_up_days,
                    $this->followUpDueDate($lead)?->toDateString() ?? 'n/a',
                    $this->leadRecipients($lead)->implode(', ') ?: 'No valid email',
                ])->all(),
            );

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($leads as $lead) {
            $recipients = $this->leadRecipients($lead);

            if ($recipients->isEmpty()) {
                $this->warn(sprintf('Skipping lead #%d: no valid recipient email.', $lead->id));
                $skipped++;

                continue;
            }

            try {
                foreach ($recipients as $recipient) {
                    Notification::route('mail', $recipient)
                        ->notify(new LeadFollowUpNotification($lead));
                }

                $lead->forceFill([
                    'follow_up_sent_at' => now(),
                ])->save();

                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $this->error(sprintf('Failed sending automatic follow up for lead #%d.', $lead->id));
                $failed++;
            }
        }

        $this->info(sprintf(
            'Automatic lead follow ups completed. Sent: %d. Skipped: %d. Failed: %d.',
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

    protected function leadsDueForFollowUp(Carbon $today): Collection
    {
        return Lead::query()
            ->where('send_automatic_follow_up', true)
            ->whereNull('follow_up_sent_at')
            ->whereNotIn('status', ['confirmed', 'transferred', 'rejected', 'lost'])
            ->orderBy('requested_at')
            ->orderBy('id')
            ->get()
            ->filter(fn (Lead $lead): bool => $this->isDueForFollowUp($lead, $today))
            ->values();
    }

    protected function isDueForFollowUp(Lead $lead, Carbon $today): bool
    {
        $dueDate = $this->followUpDueDate($lead);

        return $dueDate !== null && $dueDate->lte($today);
    }

    protected function followUpDueDate(Lead $lead): ?Carbon
    {
        $baseDate = $this->followUpBaseDate($lead);

        if (! $baseDate) {
            return null;
        }

        return $baseDate->addDays(max(1, (int) ($lead->follow_up_days ?: 7)));
    }

    protected function followUpBaseDate(Lead $lead): ?Carbon
    {
        if ($lead->requested_at) {
            return $lead->requested_at->copy()->startOfDay();
        }

        return $lead->created_at?->copy()->startOfDay();
    }

    protected function leadRecipients(Lead $lead): Collection
    {
        return collect([
            $lead->email,
            $lead->secondary_email,
        ])
            ->filter(fn ($email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
    }
}
