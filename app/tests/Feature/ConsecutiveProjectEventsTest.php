<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectCalendar;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\Role;
use App\Models\User;
use App\Support\ConsecutiveProjectEvents;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ConsecutiveProjectEventsTest extends TestCase
{
    use DatabaseTransactions;

    private function event(Project $project, string $start, ?string $end = null, array $attributes = []): ProjectEvent
    {
        return $project->projectEvents()->create(array_merge([
            'title' => 'Tasting ' . $start,
            'starts_at' => $start . ' 09:00:00',
            'ends_at' => ($end ?: $start) . ' 17:00:00',
            'is_all_day' => false,
            'program_html' => '<h3>Menu</h3><p>Seasonal dishes.</p>',
        ], $attributes));
    }

    private function signInAdmin(): void
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    public function test_group_extends_both_ways_across_months_and_stops_at_gaps(): void
    {
        $project = Project::query()->create(['name' => 'Tasting weekend', 'last_name' => 'Client']);
        $this->event($project, '2026-10-28');
        $before = $this->event($project, '2026-10-30');
        $anchor = $this->event($project, '2026-10-31');
        $after = $this->event($project, '2026-11-01');
        $this->event($project, '2026-11-03');
        $other = Project::query()->create(['name' => 'Other wedding', 'last_name' => 'Client']);
        $this->event($other, '2026-11-02');

        foreach ([$before, $anchor, $after] as $event) {
            $this->assertSame(
                [$before->id, $anchor->id, $after->id],
                app(ConsecutiveProjectEvents::class)->forEvent($event)->pluck('id')->all(),
            );
        }
    }

    public function test_multi_day_events_bridge_days_and_overlapping_events_do_not_shorten_group(): void
    {
        $project = Project::query()->create(['name' => 'Long tasting', 'last_name' => 'Client']);
        $first = $this->event($project, '2026-11-01', '2026-11-05');
        $overlap = $this->event($project, '2026-11-02');
        $last = $this->event($project, '2026-11-06', null, ['program_html' => null]);

        $this->assertSame(
            [$first->id, $overlap->id, $last->id],
            app(ConsecutiveProjectEvents::class)->forEvent($overlap)->pluck('id')->all(),
        );
    }

    public function test_popup_keeps_single_download_and_adds_group_download_with_full_range(): void
    {
        $this->signInAdmin();
        $project = Project::query()->create(['name' => 'Three tastings', 'last_name' => 'Client']);
        $this->event($project, '2026-11-12');
        $anchor = $this->event($project, '2026-11-13');
        $this->event($project, '2026-11-14');

        Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
            ->call('openCalendarItem', 'event', $anchor->id)
            ->assertSee('Export program PDF')
            ->assertSee(route('admin.projects.calendar.events.program.pdf', ['project' => $project, 'event' => $anchor]))
            ->assertSee('dal 12/11/2026 al 14/11/2026')
            ->assertSee(route('admin.projects.calendar.events.group-program.pdf', ['project' => $project, 'event' => $anchor]));
    }

    public function test_same_day_events_and_isolated_multi_day_events_do_not_offer_group_download(): void
    {
        $this->signInAdmin();
        $project = Project::query()->create(['name' => 'Separate tastings', 'last_name' => 'Client']);
        $anchor = $this->event($project, '2026-11-01');
        $this->event($project, '2026-11-01');
        $isolated = $this->event($project, '2026-11-05', '2026-11-07');

        foreach ([$anchor, $isolated] as $event) {
            $this->assertTrue(app(ConsecutiveProjectEvents::class)->forEvent($event)->isEmpty());
            Livewire::test(ViewProjectCalendar::class, ['record' => $project->id])
                ->call('openCalendarItem', 'event', $event->id)
                ->assertSee('Export program PDF')
                ->assertDontSee('Scarica programma completo PDF');
            $this->get(route('admin.projects.calendar.events.group-program.pdf', ['project' => $project, 'event' => $event]))
                ->assertNotFound();
        }
    }

    public function test_group_pdf_download_includes_programs_and_events_without_programs(): void
    {
        $this->signInAdmin();
        $project = Project::query()->create(['name' => 'Tasting program', 'last_name' => 'Client']);
        $first = $this->event($project, '2026-11-12', null, ['title' => 'Antipasti', 'program_html' => '<p>Bruschette e bollicine</p>']);
        $anchor = $this->event($project, '2026-11-13', null, ['title' => 'Primi', 'program_html' => null, 'description' => 'Risotto e pasta fresca']);
        $this->event($project, '2026-11-14', null, ['title' => 'Dessert', 'is_all_day' => true, 'program_html' => '<p>Torta nuziale</p>']);

        $response = $this->get(route('admin.projects.calendar.events.group-program.pdf', ['project' => $project, 'event' => $anchor]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('tasting-program-program-2026-11-12-2026-11-14.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $html = view('pdf.project-events-program', [
            'project' => $project,
            'events' => app(ConsecutiveProjectEvents::class)->forEvent($anchor),
            'startsAt' => $first->starts_at,
            'endsAt' => $first->starts_at->copy()->addDays(2),
            'generatedAt' => now(),
        ])->render();
        foreach (['Bruschette e bollicine', 'Risotto e pasta fresca', 'Torta nuziale', 'Tutto il giorno'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertTrue(strpos($html, 'Antipasti') < strpos($html, 'Primi'));
        $this->assertTrue(strpos($html, 'Primi') < strpos($html, 'Dessert'));

        // The original single-event endpoint and filename are unchanged, even in a group.
        $this->get(route('admin.projects.calendar.events.program.pdf', ['project' => $project, 'event' => $first]))
            ->assertOk()
            ->assertDownload('tasting-program-antipasti-program.pdf');
    }

    public function test_group_download_rejects_an_event_from_another_project(): void
    {
        $this->signInAdmin();
        $project = Project::query()->create(['name' => 'Project A', 'last_name' => 'Client']);
        $other = Project::query()->create(['name' => 'Project B', 'last_name' => 'Client']);
        $event = $this->event($other, '2026-11-12');
        $this->event($other, '2026-11-13');
        $this->get(route('admin.projects.calendar.events.group-program.pdf', ['project' => $project, 'event' => $event]))
            ->assertNotFound();
    }

    public function test_group_download_requires_authentication(): void
    {
        $project = Project::query()->create(['name' => 'Private tastings', 'last_name' => 'Client']);
        $event = $this->event($project, '2026-11-12');
        $this->event($project, '2026-11-13');
        $this->getJson(route('admin.projects.calendar.events.group-program.pdf', ['project' => $project, 'event' => $event]))
            ->assertUnauthorized();
    }

    public function test_customer_can_only_download_group_for_assigned_projects(): void
    {
        $role = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($customer);
        $project = Project::query()->create(['name' => 'Assigned tastings', 'last_name' => 'Client']);
        $event = $this->event($project, '2026-11-12');
        $this->event($project, '2026-11-13');
        $url = route('admin.projects.calendar.events.group-program.pdf', ['project' => $project, 'event' => $event]);

        $this->get($url)->assertNotFound();
        $project->users()->attach($customer);
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
