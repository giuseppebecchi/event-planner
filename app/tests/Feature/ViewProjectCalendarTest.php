<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectCalendar;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ViewProjectCalendarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_created_project_event_moves_calendar_to_event_month(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $project = Project::query()->create([
            'name' => 'Calendar wedding',
            'last_name' => 'Client',
            'event_date' => '2027-09-24',
            'event_start_date' => '2027-09-24',
            'event_end_date' => '2027-09-26',
        ]);

        Livewire::test(ViewProjectCalendar::class, [
            'record' => $project->id,
        ])
            ->assertSet('visibleMonth', '2026-07')
            ->set('eventForm.title', 'Venue inspection')
            ->set('eventForm.start_date', '2026-11-07')
            ->set('eventForm.end_date', '2026-11-07')
            ->call('saveProjectEvent')
            ->assertHasNoErrors()
            ->assertSet('visibleMonth', '2026-11');

        $this->assertDatabaseHas('project_events', [
            'project_id' => $project->id,
            'title' => 'Venue inspection',
            'starts_at' => '2026-11-07 00:00:00',
        ]);
    }

    public function test_single_day_project_event_can_be_created_after_today(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $project = Project::query()->create([
            'name' => 'Tomorrow calendar wedding',
            'last_name' => 'Client',
        ]);

        Livewire::test(ViewProjectCalendar::class, [
            'record' => $project->id,
        ])
            ->set('eventForm.title', 'Tomorrow appointment')
            ->set('eventForm.start_date', '2026-07-21')
            ->call('saveProjectEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_events', [
            'project_id' => $project->id,
            'title' => 'Tomorrow appointment',
            'starts_at' => '2026-07-21 00:00:00',
            'ends_at' => '2026-07-21 23:59:59',
        ]);
    }
}
