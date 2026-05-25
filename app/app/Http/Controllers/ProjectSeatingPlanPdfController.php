<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Project;
use App\Models\ProjectSeatingPlan;
use App\Support\SeatingPlanMapRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ProjectSeatingPlanPdfController
{
    public function __invoke(Project $project, ProjectSeatingPlan $seatingPlan): Response
    {
        abort_unless($seatingPlan->project_id === $project->id, 404);

        $seatingPlan->load('tables');
        $people = $project->guests()
            ->orderBy('group_name')
            ->orderBy('primary_last_name')
            ->orderBy('primary_first_name')
            ->get()
            ->flatMap(fn (Guest $guest): array => $this->flattenGuestParty($guest))
            ->keyBy('key');

        $tables = $seatingPlan->tables->map(function ($table) use ($people): array {
            $assignments = collect($table->guest_assignments_json ?? [])
                ->mapWithKeys(fn ($guestKey, $seat): array => [(int) $seat => $people->get($guestKey)])
                ->filter()
                ->sortKeys();

            return [
                'model' => $table,
                'seat_count' => $table->seatCount(),
                'assigned_count' => $assignments->count(),
                'assignments' => $assignments,
            ];
        });

        $mapSvg = app(SeatingPlanMapRenderer::class)->render($seatingPlan, $people, true);
        $mapDataUri = 'data:image/svg+xml;base64,' . base64_encode($mapSvg);

        $pdf = Pdf::loadView('pdf.project-seating-plan', [
            'project' => $project,
            'seatingPlan' => $seatingPlan,
            'tables' => $tables,
            'mapDataUri' => $mapDataUri,
            'stats' => [
                'tables' => $tables->count(),
                'seats' => $tables->sum('seat_count'),
                'assigned' => $tables->sum('assigned_count'),
            ],
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . str($seatingPlan->name)->slug() . '-seating-plan.pdf"',
        ]);
    }

    protected function flattenGuestParty(Guest $guest): array
    {
        $people = [];

        if (filled($guest->primary_first_name) || filled($guest->primary_last_name)) {
            $people[] = $this->personPayload('guest:' . $guest->id . ':primary', $guest->primary_first_name, $guest->primary_last_name, $guest->group_name ?: $guest->displayName());
        }

        if (filled($guest->partner_first_name) || filled($guest->partner_last_name)) {
            $people[] = $this->personPayload('guest:' . $guest->id . ':partner', $guest->partner_first_name, $guest->partner_last_name, $guest->group_name ?: $guest->displayName());
        }

        foreach ($guest->normalizedAdditionalGuests() as $index => $additionalGuest) {
            if (blank($additionalGuest['first_name']) && blank($additionalGuest['last_name'])) {
                continue;
            }

            $people[] = $this->personPayload('guest:' . $guest->id . ':additional:' . $index, $additionalGuest['first_name'], $additionalGuest['last_name'], $guest->group_name ?: $guest->displayName());
        }

        return $people;
    }

    protected function personPayload(string $key, ?string $firstName, ?string $lastName, string $group): array
    {
        $firstName = trim((string) $firstName);
        $lastName = trim((string) $lastName);
        $label = trim(collect([$firstName, $lastName])->filter()->implode(' '));

        return [
            'key' => $key,
            'label' => $label ?: 'Unnamed guest',
            'group' => $group,
        ];
    }
}
