<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ProjectEventProgramPdfController extends Controller
{
    public function __invoke(Project $project, ProjectEvent $event)
    {
        abort_if((int) $event->project_id !== (int) $project->id, 404);
        abort_if(blank($event->program_html), 404);

        $pdf = Pdf::loadView('pdf.project-event-program', [
            'project' => $project,
            'event' => $event,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($this->filename($project, $event));
    }

    protected function filename(Project $project, ProjectEvent $event): string
    {
        $name = collect([
            $project->name,
            $event->title,
            'program',
        ])->filter()->implode(' ');

        return (Str::slug($name) ?: 'event-program') . '.pdf';
    }
}
