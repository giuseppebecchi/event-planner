<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadResource\Pages\ViewLeadFormData;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Notifications\LeadQuestionnaireRequestNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lead_questionnaire_can_be_marked_as_sent_manually(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $lead = Lead::query()->create([
            'couple_name' => 'Manual Mail Couple',
            'email' => 'manual@example.com',
            'form_sent_at' => null,
        ]);

        Livewire::test(ViewLeadFormData::class, [
            'record' => $lead->getRouteKey(),
        ])
            ->assertActionEnabled('markAsSent')
            ->callAction('markAsSent')
            ->assertActionDisabled('markAsSent')
            ->assertHasNoErrors();

        $this->assertNotNull($lead->refresh()->form_sent_at);
    }

    public function test_lead_questionnaire_can_be_sent_by_mail(): void
    {
        Notification::fake();

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Template::query()->updateOrCreate(
            ['slug' => 'mail-lead-questionnaire', 'language' => 'en'],
            [
                'title' => 'Lead questionnaire request',
                'subject' => 'Wedding questionnaire',
                'type' => Template::TYPE_HTML,
                'content' => '<p>Hi {{ client_names }}</p><p><a href="{{ questionnaire_link }}">Open the questionnaire</a></p>',
            ],
        );

        $lead = Lead::query()->create([
            'couple_name' => 'Mail Couple',
            'email' => 'client@example.com',
            'secondary_email' => 'partner@example.com',
            'form_sent_at' => null,
        ]);

        Livewire::test(ViewLeadFormData::class, [
            'record' => $lead->getRouteKey(),
        ])
            ->assertActionExists('sendFormByMail')
            ->callAction('sendFormByMail')
            ->assertHasNoErrors();

        $this->assertNotNull($lead->refresh()->form_sent_at);

        Notification::assertSentOnDemand(LeadQuestionnaireRequestNotification::class, function ($notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && $notifiable->routes['mail'] === ['client@example.com', 'partner@example.com'];
        });
    }
}
