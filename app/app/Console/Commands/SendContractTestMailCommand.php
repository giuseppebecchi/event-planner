<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Notifications\LeadContractNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendContractTestMailCommand extends Command
{
    protected $signature = 'contracts:send-test {lead? : Lead ID to use for the contract} {--to= : Override MAIL_TEST_TO for this run}';

    protected $description = 'Send a contract email to the configured test recipient.';

    public function handle(): int
    {
        $recipient = trim((string) ($this->option('to') ?: config('mail.test_to')));

        $validator = Validator::make(
            ['email' => $recipient],
            ['email' => ['required', 'email']]
        );

        if ($validator->fails()) {
            $this->error('Set a valid MAIL_TEST_TO email address, or pass --to=test@example.com.');

            return self::FAILURE;
        }

        $lead = $this->lead();

        if (! $lead) {
            $this->error('No lead found. Create a lead first or pass a valid lead ID.');

            return self::FAILURE;
        }

        Notification::route('mail', $recipient)
            ->notify(new LeadContractNotification($lead));

        $this->info(sprintf(
            'Contract test email sent to %s using lead #%d (%s).',
            $recipient,
            $lead->id,
            $lead->couple_name ?: $lead->email ?: 'Lead'
        ));

        return self::SUCCESS;
    }

    protected function lead(): ?Lead
    {
        $leadId = $this->argument('lead');

        if (filled($leadId)) {
            return Lead::query()->find((int) $leadId);
        }

        return Lead::query()->orderBy('id')->first();
    }
}
