<?php

namespace App\Http\Controllers;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectEvent;
use App\Support\ConsecutiveProjectEvents;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ProjectEventsProgramPdfController extends Controller
{
    public function __invoke(Project $project, ProjectEvent $event, ConsecutiveProjectEvents $consecutiveEvents)
    {
        abort_unless(ProjectResource::getEloquentQuery()->whereKey($project->id)->exists(), 404);
        abort_if((int) $event->project_id !== (int) $project->id, 404);

        $events = $consecutiveEvents->forEvent($event);
        abort_if($events->isEmpty(), 404);

        $startsAt = $events->first()->starts_at;
        $endsAt = $events->max(fn (ProjectEvent $item) => $item->ends_at ?: $item->starts_at);

        return Pdf::loadView('pdf.project-events-program', [
            'project' => $project,
            'events' => $events,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->download(sprintf(
            '%s-program-%s-%s.pdf',
            Str::slug($project->name) ?: 'project',
            $startsAt->format('Y-m-d'),
            $endsAt->format('Y-m-d'),
        ));
    }
}
