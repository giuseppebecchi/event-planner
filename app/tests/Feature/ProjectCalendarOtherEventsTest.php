<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectCalendar;
use App\Models\Checklist;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\ProjectEvent;
use App\Models\Role;
use App\Models\User;
use App\Support\ConsecutiveProjectEvents;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCalendarOtherEventsTest extends TestCase
{
    use DatabaseTransactions;

    private function project(string $name): Project
    {
        return Project::query()->create(['name' => $name, 'last_name' => 'Client']);
    }

    private function event(Project $project, string $title, string $start, ?string $end = null): ProjectEvent
    {
        return $project->projectEvents()->create([
            'title' => $title,
            'starts_at' => $start,
            'ends_at' => $end,
            'is_all_day' => false,
            'program_html' => '<p>Event program.</p>',
        ]);
    }

    private function signIn(string $roleName = Role::ADMIN): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_other_events_show_in_gray_with_project_name_but_other_payments_and_checklists_do_not(): void
    {
        $this->signIn();
        $project = $this->project('Current wedding');
        $other = $this->project('Other wedding');
        $ownEvent = $this->event($project, 'Own appointment', '2026-12-02 09:00:00', '2026-12-02 10:00:00');
        $external = $this->event($other, 'Other tasting', '2026-12-02 11:00:00', '2026-12-02 12:00:00');
        Payment::query()->create([
            'project_id' => $other->id,
            'reason' => 'Private other payment',
            'amount' => 100,
            'due_date' => '2026-12-02',
            'payment_status' => Payment::STATUS_UNPAID,
        ]);
        $checklist = Checklist::query()->create(['title' => 'Other checklist', 'options' => []]);
        ProjectChecklistOption::query()->create([
            'project_id' => $other->id,
            'checkbox_id' => $checklist->id,
            'order' => 1,
            'title' => 'Private other task',
            'assigned_to' => 'client',
            'due_date' => '2026-12-02',
            'enabled' => true,
            'completed' => false,
        ]);

        $component = Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
            ->set('visibleMonth', '2026-12')
            ->assertSee('Own appointment')
            ->assertSee('Other tasting')
            ->assertSee('Other wedding')
            ->assertDontSee('Private other payment')
            ->assertDontSee('Private other task');
        $items = $component->instance()->getCalendarCells()->firstWhere('date_key', '2026-12-02')['items'];
        $this->assertSame('sky', $items->firstWhere('id', $ownEvent->id)['color']);
        $this->assertSame('gray', $items->firstWhere('id', $external->id)['color']);
        $this->assertSame(1, $component->instance()->getCalendarSummary()['events']);
        $this->assertSame(1, $component->instance()->getCalendarSummary()['total']);
        $component->call('setCalendarView', 'list')
            ->assertSee('Other tasting')
            ->assertSee('Other wedding')
            ->assertSeeHtml('is-other-project');
    }

    public function test_external_popup_links_to_its_project_without_current_project_edit_or_pdf_actions(): void
    {
        $this->signIn();
        $project = $this->project('Current wedding');
        $other = $this->project('Other wedding');
        $event = $this->event($other, 'External appointment', '2026-12-02 11:00:00', '2026-12-02 12:00:00');

        $component = Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
            ->set('visibleMonth', '2026-12')
            ->call('openCalendarItem', 'other_event', $event->id)
            ->assertSee('Other project event')
            ->assertSee('Open project calendar')
            ->assertDontSee('Export program PDF')
            ->assertDontSee('Download full program PDF')
            ->assertDontSeeHtml('wire:click="editCalendarEvent')
            ->assertDontSeeHtml('wire:click="promptDeleteCalendarEvent');

        $this->assertSame('Other wedding', $component->instance()->getSelectedCalendarItem()['project_name']);
        $this->assertSame('other_event', $component->instance()->getSelectedCalendarItem()['kind']);
    }

    public function test_external_events_respect_customer_access_and_exclude_deleted_projects(): void
    {
        $customer = $this->signIn(Role::CUSTOMER);
        $project = $this->project('Assigned current wedding');
        $allowed = $this->project('Assigned other wedding');
        $hidden = $this->project('Unassigned wedding');
        $deleted = $this->project('Deleted wedding');
        foreach ([$project, $allowed, $deleted] as $assigned) {
            $assigned->users()->attach($customer);
        }
        $this->event($allowed, 'Allowed appointment', '2026-12-02 09:00:00');
        $secret = $this->event($hidden, 'Secret appointment', '2026-12-02 09:00:00');
        $this->event($deleted, 'Deleted appointment', '2026-12-02 09:00:00');
        $deleted->delete();

        $component = Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
            ->set('visibleMonth', '2026-12')
            ->assertSee('Allowed appointment')
            ->assertDontSee('Secret appointment')
            ->assertDontSee('Deleted appointment')
            ->call('openCalendarItem', 'other_event', $secret->id);
        $this->assertNull($component->instance()->getSelectedCalendarItem());
    }

    public function test_multi_day_external_events_cover_each_visible_day_without_entering_project_pdf_groups(): void
    {
        $this->signIn();
        $project = $this->project('Current wedding');
        $other = $this->project('Other wedding');
        $own = $this->event($project, 'Own appointment', '2026-12-02 09:00:00');
        $external = $this->event($other, 'Other project stay', '2026-11-28 15:00:00', '2026-12-03 10:00:00');
        $this->event($other, 'Out of range appointment', '2027-02-01 09:00:00');

        $component = Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
            ->set('visibleMonth', '2026-12')
            ->assertDontSee('Out of range appointment');
        $cells = $component->instance()->getCalendarCells();
        foreach (['2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03'] as $date) {
            $this->assertContains($external->id, $cells->firstWhere('date_key', $date)['items']->where('kind', 'other_event')->pluck('id')->all());
        }
        $this->assertTrue($cells->firstWhere('date_key', '2026-12-04')['items']->where('kind', 'other_event')->isEmpty());
        $this->assertTrue(app(ConsecutiveProjectEvents::class)->forEvent($own)->isEmpty());
        $component->call('nextMonth')->assertDontSee('Other project stay');
    }

    public function test_busy_days_keep_an_external_event_indicator_visible_even_when_items_are_collapsed(): void
    {
        $customer = $this->signIn(Role::CUSTOMER);
        $project = $this->project('Busy current wedding');
        $other = $this->project('Other wedding');
        $project->users()->attach($customer);
        $other->users()->attach($customer);
        for ($index = 0; $index < 5; $index++) {
            $this->event($project, 'Current appointment ' . $index, '2026-12-02 09:00:00');
        }
        $this->event($other, 'External booking', '2026-12-02 11:00:00');

        Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
            ->set('visibleMonth', '2026-12')
            ->assertSee('Other projects: 1 event')
            ->assertSeeHtml('wm-calendar-other-projects-recap');
    }
}
