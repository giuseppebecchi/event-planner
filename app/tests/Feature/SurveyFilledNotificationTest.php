<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SurveyFilledNotification;
use App\Support\LeadQuestionnaire;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SurveyFilledNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lead_questionnaire_notifies_only_enabled_admins(): void
    {
        Notification::fake();

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);

        $enabledAdmin = User::factory()->create([
            'role_id' => $adminRole->id,
            'notification_enabled' => true,
        ]);
        $disabledAdmin = User::factory()->create([
            'role_id' => $adminRole->id,
            'notification_enabled' => false,
        ]);
        $enabledCustomer = User::factory()->create([
            'role_id' => $customerRole->id,
            'notification_enabled' => true,
        ]);

        $lead = Lead::query()->create([
            'couple_name' => 'Original Couple',
            'email' => 'lead@example.com',
        ]);

        $this->post(route('public.lead-form.submit', $lead->public_form_hash), $this->validPayload())
            ->assertRedirect(route('public.lead-form.show', $lead->public_form_hash));

        Notification::assertSentTo($enabledAdmin, SurveyFilledNotification::class);
        Notification::assertNotSentTo($disabledAdmin, SurveyFilledNotification::class);
        Notification::assertNotSentTo($enabledCustomer, SurveyFilledNotification::class);
    }

    public function test_survey_filled_notification_contains_mail_and_database_content(): void
    {
        $user = User::factory()->create(['name' => 'Irene']);
        $lead = Lead::query()->create([
            'couple_name' => 'Alex & Sam',
            'email' => 'lead@example.com',
        ]);

        $notification = new SurveyFilledNotification($lead);
        $mail = $notification->toMail($user);
        $database = $notification->toDatabase($user);

        $this->assertSame('Survey Filled', $mail->subject);
        $this->assertContains('Alex & Sam has completed the lead questionnaire.', $mail->introLines);
        $this->assertSame('Open Lead', $mail->actionText);

        $this->assertSame('Survey Filled', $database['title']);
        $this->assertSame('Alex & Sam has completed the lead questionnaire.', $database['body']);
    }

    protected function validPayload(): array
    {
        return collect(LeadQuestionnaire::definition())
            ->mapWithKeys(function (array $question): array {
                $key = $question['key'];
                $type = $question['type'] ?? 'text';

                if ($type === 'checkboxes') {
                    return [$key => [($question['options'] ?? ['Option'])[0]]];
                }

                if (in_array($type, ['radio', 'select'], true)) {
                    return [$key => ($question['options'] ?? ['Option'])[0]];
                }

                return [$key => ($question['required'] ?? false) ? 'Example answer' : null];
            })
            ->all();
    }
}
