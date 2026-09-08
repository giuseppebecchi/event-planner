<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class LeadAutomaticFollowUpFormTest extends TestCase
{
    use DatabaseTransactions;

    public function test_automatic_follow_up_days_are_visible_only_when_enabled(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Livewire::test(CreateLead::class)
            ->assertFormFieldHidden('follow_up_days')
            ->fillForm([
                'send_automatic_follow_up' => true,
            ])
            ->assertFormFieldVisible('follow_up_days');
    }

    public function test_automatic_follow_up_settings_can_be_saved_when_creating_a_lead(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Livewire::test(CreateLead::class)
            ->fillForm([
                'requested_at' => '2026-09-08',
                'couple_name' => 'Follow Up Couple',
                'email' => 'follow-up@example.com',
                'status' => 'new',
                'evaluation_outcome' => 'maybe',
                'send_automatic_follow_up' => true,
                'follow_up_days' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'couple_name' => 'Follow Up Couple',
            'email' => 'follow-up@example.com',
            'send_automatic_follow_up' => true,
            'follow_up_days' => 10,
            'follow_up_sent_at' => null,
        ]);

        $this->assertSame(1, Lead::query()->where('email', 'follow-up@example.com')->count());
    }
}
