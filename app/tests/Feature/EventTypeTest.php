<?php

namespace Tests\Feature;

use App\Filament\Resources\EventTypeResource;
use App\Filament\Resources\LeadResource\Pages\ViewLeadContract;
use App\Models\EventType;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class EventTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_event_types_are_available_in_setup(): void
    {
        $this->assertSame([
            'wedding' => 'Wedding',
            'private-event' => 'Private event',
            'elopement' => 'Elopement',
            'proposal' => 'Proposal',
        ], EventType::query()->orderBy('order')->pluck('name', 'slug')->all());
    }

    public function test_new_leads_and_projects_default_to_wedding(): void
    {
        $weddingId = EventType::weddingId();
        $lead = Lead::query()->create(['couple_name' => 'Default event lead']);
        $project = Project::query()->create([
            'name' => 'Default event project',
            'last_name' => 'Client',
        ]);

        $this->assertSame($weddingId, $lead->event_type_id);
        $this->assertSame($weddingId, $project->event_type_id);
    }

    public function test_project_generated_from_lead_keeps_event_type(): void
    {
        $this->actingAsAdmin();
        $proposal = EventType::query()->where('slug', 'proposal')->firstOrFail();
        $lead = Lead::query()->create([
            'couple_name' => 'Proposal Couple',
            'event_type_id' => $proposal->id,
        ]);
        $page = Livewire::test(ViewLeadContract::class, ['record' => $lead->id])->instance();
        $payload = (new ReflectionMethod($page, 'projectPayloadFromLead'))->invoke($page, $lead);

        $this->assertSame($proposal->id, $payload['event_type_id']);
        $this->assertSame('Proposal - Proposal Couple', $payload['name']);
    }

    public function test_only_administrators_can_manage_event_types(): void
    {
        $this->actingAsAdmin();
        $this->assertTrue(EventTypeResource::canViewAny());
        $this->assertFalse(EventTypeResource::canDelete(EventType::query()->where('slug', 'wedding')->firstOrFail()));

        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $this->actingAs(User::factory()->create(['role_id' => $customerRole->id]));

        $this->assertFalse(EventTypeResource::canViewAny());
    }

    protected function actingAsAdmin(): void
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);

        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }
}
