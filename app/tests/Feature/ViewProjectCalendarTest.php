<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectCalendar;
use App\Filament\Resources\ProjectResource;
use App\Models\Checklist;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ViewProjectCalendarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-20 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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

    public function test_today_cell_shows_overdue_checklist_recap_and_popup(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $project = Project::query()->create([
            'name' => 'Overdue calendar wedding',
            'last_name' => 'Client',
        ]);
        $checklist = Checklist::query()->create([
            'title' => 'Planning checklist',
            'options' => [],
        ]);

        ProjectChecklistOption::query()->create([
            'project_id' => $project->id,
            'checkbox_id' => $checklist->id,
            'order' => 1,
            'title' => 'Book florist',
            'assigned_to' => 'client',
            'due_date' => '2026-07-15',
            'enabled' => true,
            'completed' => false,
        ]);
        ProjectChecklistOption::query()->create([
            'project_id' => $project->id,
            'checkbox_id' => $checklist->id,
            'order' => 2,
            'title' => 'Confirm music',
            'assigned_to' => 'client',
            'due_date' => '2026-07-18',
            'enabled' => true,
            'completed' => false,
        ]);
        ProjectChecklistOption::query()->create([
            'project_id' => $project->id,
            'checkbox_id' => $checklist->id,
            'order' => 3,
            'title' => 'Completed overdue task',
            'assigned_to' => 'client',
            'due_date' => '2026-07-10',
            'enabled' => true,
            'completed' => true,
        ]);

        $component = Livewire::test(ViewProjectCalendar::class, [
            'record' => $project->id,
        ])
            ->assertSee('2')
            ->assertSee('overdue checklist tasks')
            ->call('openOverdueChecklistSummary')
            ->assertSet('showOverdueChecklistSummary', true)
            ->assertSee('Book florist')
            ->assertSee('Confirm music')
            ->assertSee(ProjectResource::getUrl('checklist', ['record' => $project]))
            ->call('closeOverdueChecklistSummary')
            ->assertSet('showOverdueChecklistSummary', false);

        $this->assertSame(
            ['Book florist', 'Confirm music'],
            $component->instance()->getOverdueChecklistItems()->pluck('title')->all(),
        );
    }

    public function test_project_event_can_be_deleted_from_calendar_detail(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $project = Project::query()->create([
            'name' => 'Delete calendar wedding',
            'last_name' => 'Client',
        ]);
        $event = ProjectEvent::query()->create([
            'project_id' => $project->id,
            'title' => 'Makeup trial',
            'description' => 'Confirm details',
            'starts_at' => '2026-11-28 09:00:00',
            'ends_at' => '2026-11-28 10:00:00',
            'is_all_day' => false,
        ]);

        Livewire::test(ViewProjectCalendar::class, [
            'record' => $project->id,
        ])
            ->call('openCalendarItem', 'event', $event->id)
            ->assertSet('selectedCalendarItemKind', 'event')
            ->assertSet('selectedCalendarItemId', $event->id)
            ->assertSee('Delete')
            ->call('promptDeleteCalendarEvent', $event->id)
            ->assertSet('confirmDeleteProjectEventId', $event->id)
            ->assertSet('selectedCalendarItemKind', null)
            ->assertSet('selectedCalendarItemId', null)
            ->assertSee('Delete event?')
            ->call('cancelDeleteCalendarEvent')
            ->assertSet('confirmDeleteProjectEventId', null);

        $this->assertDatabaseHas('project_events', [
            'id' => $event->id,
        ]);

        Livewire::test(ViewProjectCalendar::class, [
            'record' => $project->id,
        ])
            ->call('promptDeleteCalendarEvent', $event->id)
            ->assertSet('confirmDeleteProjectEventId', $event->id)
            ->call('confirmDeleteCalendarEvent')
            ->assertSet('confirmDeleteProjectEventId', null);

        $this->assertDatabaseMissing('project_events', [
            'id' => $event->id,
        ]);
    }
}
