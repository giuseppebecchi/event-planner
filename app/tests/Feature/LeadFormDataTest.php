<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadResource\Pages\ViewLeadFormData;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
