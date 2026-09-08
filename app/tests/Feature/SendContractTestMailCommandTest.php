<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Notifications\LeadContractNotification;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendContractTestMailCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_contract_test_mail_can_be_sent_to_the_given_recipient(): void
    {
        Notification::fake();

        $lead = Lead::query()->create([
            'couple_name' => 'Test Couple',
            'email' => 'client@example.com',
        ]);

        $this->artisan('contracts:send-test', [
            'lead' => $lead->id,
            '--to' => 'test-recipient@example.com',
        ])
            ->expectsOutput(sprintf(
                'Contract test email sent to test-recipient@example.com using lead #%d (Test Couple).',
                $lead->id,
            ))
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentOnDemand(
            LeadContractNotification::class,
            fn (LeadContractNotification $notification, array $channels, object $notifiable): bool => in_array('mail', $channels, true)
                && $notifiable->routes['mail'] === 'test-recipient@example.com',
        );
    }

    public function test_contract_test_mail_requires_a_valid_recipient(): void
    {
        Notification::fake();

        Lead::query()->create([
            'couple_name' => 'Test Couple',
            'email' => 'client@example.com',
        ]);

        $this->artisan('contracts:send-test', [
            '--to' => 'invalid-email',
        ])
            ->expectsOutput('Set a valid MAIL_TEST_TO email address, or pass --to=test@example.com.')
            ->assertExitCode(Command::FAILURE);

        Notification::assertNothingSent();
    }
}
