<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectCalendar;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CalendarMonthOccurrencesTest extends TestCase
{
    use DatabaseTransactions;

    public static function calendarsAndBoundaryDates(): array
    {
        return [
            'project, on grid boundary' => [ViewProjectCalendar::class, '2026-11-30'],
            'project, before grid boundary' => [ViewProjectCalendar::class, '2026-11-29'],
            'master, on grid boundary' => [Calendar::class, '2026-11-30'],
            'master, before grid boundary' => [Calendar::class, '2026-11-29'],
        ];
    }

    #[DataProvider('calendarsAndBoundaryDates')]
    public function test_events_remain_visible_when_navigating_between_overlapping_month_grids(string $page, string $boundaryDate): void
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $project = Project::query()->create([
            'name' => 'Stacey and Eric regression',
            'last_name' => 'Client',
            'status' => 'confirmed',
            'event_start_date' => '2027-05-11',
        ]);

        // Deliberately create events out of chronological order, as in the reported calendar.
        $stay = $this->event($project, 'Stay', '2026-12-01 15:00:00', '2026-12-03 10:00:00');
        $visit = $this->event($project, 'Site visit', '2026-12-02 10:00:00', '2026-12-02 12:00:00');
        $this->event($project, 'Boundary appointment', $boundaryDate . ' 08:30:00', '2026-11-30 11:30:00');
        $firstDinner = $this->event($project, 'First dinner', '2026-12-01 19:00:00', '2026-12-01 21:00:00');
        $tasting = $this->event($project, 'Food tasting', '2026-12-02 10:00:00', '2026-12-02 16:00:00');
        $secondDinner = $this->event($project, 'Second dinner', '2026-12-02 19:00:00', '2026-12-02 21:00:00');

        $component = Livewire::test($page, $page === ViewProjectCalendar::class ? ['record' => $project->id] : [])
            ->set('visibleMonth', '2026-11');
        $expected = [
            '2026-12-01' => [$stay->id, $firstDinner->id],
            '2026-12-02' => [$stay->id, $visit->id, $tasting->id, $secondDinner->id],
            '2026-12-03' => [$stay->id],
        ];
        $projectEventIds = $project->projectEvents()->pluck('id')->all();

        foreach (['2026-11', '2026-12', '2026-11', '2026-12'] as $index => $month) {
            if ($index > 0) {
                $component->call($month === '2026-12' ? 'nextMonth' : 'previousMonth');
            }
            $component->assertSet('visibleMonth', $month);

            // Repeated calculations must also give identical results within the same render.
            for ($repeat = 0; $repeat < 2; $repeat++) {
                $cells = $component->instance()->getCalendarCells();
                foreach ($expected as $date => $ids) {
                    $cell = $cells->firstWhere('date_key', $date);
                    $this->assertNotNull($cell);
                    $this->assertEqualsCanonicalizing(
                        $ids,
                        $cell['items']->where('kind', 'event')->whereIn('id', $projectEventIds)->pluck('id')->all(),
                        $page . ' month ' . $month . ' day ' . $date,
                    );
                }
            }
        }
    }

    private function event(Project $project, string $title, string $start, string $end): ProjectEvent
    {
        return $project->projectEvents()->create([
            'title' => $title,
            'starts_at' => $start,
            'ends_at' => $end,
            'is_all_day' => false,
        ]);
    }
}
