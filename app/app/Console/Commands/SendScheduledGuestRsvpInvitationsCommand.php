<?php

namespace App\Console\Commands;

use App\Models\Guest;
use App\Notifications\GuestRsvpInvitationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification as MailNotification;
use Throwable;

class SendScheduledGuestRsvpInvitationsCommand extends Command
{
    protected $signature = 'rsvp:send-scheduled-invitations {--limit=10 : Maximum emails to send per run}';

    protected $description = 'Send scheduled RSVP invitation emails.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $sent = 0;
        $failed = 0;

        Guest::query()
            ->with('project')
            ->whereNotNull('rsvp_invitation_scheduled_at')
            ->whereNull('rsvp_invitation_sent_at')
            ->where('rsvp_invitation_scheduled_at', '<=', now())
            ->orderBy('rsvp_invitation_scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Guest $guest) use (&$sent, &$failed): void {
                if (! $this->guestHasValidEmail($guest)) {
                    $guest->forceFill(['rsvp_invitation_scheduled_at' => null])->save();

                    return;
                }

                try {
                    MailNotification::route('mail', $guest->email)
                        ->notify(new GuestRsvpInvitationNotification($guest->project, $guest));

                    $guest->forceFill([
                        'invite_sent' => 1,
                        'rsvp_invitation_sent_at' => now(),
                    ])->save();

                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                }
            });

        $this->info(sprintf('Scheduled RSVP invitations sent: %d. Failed: %d.', $sent, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function guestHasValidEmail(Guest $guest): bool
    {
        return is_string($guest->email) && filter_var($guest->email, FILTER_VALIDATE_EMAIL);
    }
}
