<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Template;
use App\Notifications\LeadFollowUpNotification;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendLeadFollowUpsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-08 09:00:00');

        Template::query()->updateOrCreate(
            ['slug' => 'mail-lead-follow-up', 'language' => 'en'],
            [
                'title' => 'Lead follow up',
                'subject' => 'Just following up',
                'type' => Template::TYPE_HTML,
                'content' => '<p>Hi {{ couple_names }},</p><p>Following up.</p>',
            ],
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dry_run_previews_due_follow_ups_without_sending(): void
    {
        Notification::fake();

        $lead = Lead::query()->create([
            'requested_at' => '2026-09-01',
            'couple_name' => 'Dry Run Couple',
            'email' => 'primary@example.com',
            'status' => 'new',
            'send_automatic_follow_up' => true,
            'follow_up_days' => 7,
            'follow_up_sent_at' => null,
        ]);

        $this->artisan('leads:send-follow-ups', [
            '--date' => '2026-09-08',
            '--dry-run' => true,
        ])
            ->expectsOutput('Automatic lead follow ups preview for 2026-09-08:')
            ->assertExitCode(Command::SUCCESS);

        $this->assertNull($lead->refresh()->follow_up_sent_at);
        Notification::assertNothingSent();
    }

    public function test_due_follow_up_is_sent_to_lead_recipients_once(): void
    {
        Notification::fake();

        $dueLead = Lead::query()->create([
            'requested_at' => '2026-08-29',
            'couple_name' => 'Due Couple',
            'email' => 'primary@example.com',
            'secondary_email' => 'partner@example.com',
            'status' => 'new',
            'send_automatic_follow_up' => true,
            'follow_up_days' => 10,
            'follow_up_sent_at' => null,
        ]);

        Lead::query()->create([
            'requested_at' => '2026-09-05',
            'couple_name' => 'Too Early Couple',
            'email' => 'early@example.com',
            'status' => 'new',
            'send_automatic_follow_up' => true,
            'follow_up_days' => 7,
            'follow_up_sent_at' => null,
        ]);

        Lead::query()->create([
            'requested_at' => '2026-08-29',
            'couple_name' => 'Disabled Couple',
            'email' => 'disabled@example.com',
            'status' => 'new',
            'send_automatic_follow_up' => false,
            'follow_up_days' => 7,
            'follow_up_sent_at' => null,
        ]);

        $this->artisan('leads:send-follow-ups', [
            '--date' => '2026-09-08',
        ])
            ->expectsOutput('Automatic lead follow ups completed. Sent: 1. Skipped: 0. Failed: 0.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertNotNull($dueLead->refresh()->follow_up_sent_at);

        Notification::assertSentOnDemandTimes(LeadFollowUpNotification::class, 2);
        Notification::assertSentOnDemand(LeadFollowUpNotification::class, function ($notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && in_array($notifiable->routes['mail'], ['primary@example.com', 'partner@example.com'], true);
        });
    }
}
