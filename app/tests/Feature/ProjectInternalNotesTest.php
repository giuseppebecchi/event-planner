<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectInternalNotesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_staff_can_manage_internal_notes_from_project_dashboard(): void
    {
        $project = Project::query()->create([
            'name' => 'Internal notes wedding',
            'last_name' => 'Client',
        ]);
        $this->actingAs($this->createUser(Role::COLLABORATOR));

        Livewire::test(ViewProject::class, ['record' => $project->id])
            ->assertSee('Internal notes')
            ->assertSee('No internal notes yet.')
            ->callAction('manageInternalNotes', [
                'internal_notes' => '<p>Call the venue before confirming the layout.</p>',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(
            '<p>Call the venue before confirming the layout.</p>',
            $project->fresh()->internal_notes,
        );
    }

    public function test_internal_notes_are_hidden_from_customer_dashboard(): void
    {
        $project = Project::query()->create([
            'name' => 'Private notes wedding',
            'last_name' => 'Client',
            'internal_notes' => '<p>Private planner note</p>',
        ]);
        $customer = $this->createUser(Role::CUSTOMER);
        $project->users()->attach($customer);
        $this->actingAs($customer);

        Livewire::test(ViewProject::class, ['record' => $project->id])
            ->assertDontSee('Internal notes')
            ->assertDontSee('Private planner note');
    }

    public function test_long_internal_notes_offer_read_all_control(): void
    {
        $project = Project::query()->create([
            'name' => 'Long notes wedding',
            'last_name' => 'Client',
            'internal_notes' => '<p>'.str_repeat('Long internal planning note. ', 20).'</p>',
        ]);
        $this->actingAs($this->createUser(Role::ADMIN));

        Livewire::test(ViewProject::class, ['record' => $project->id])
            ->assertSee('Read all')
            ->assertSee('Manage notes');
    }

    protected function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
