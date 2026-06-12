<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class ResetProjectTimelineCommand extends Command
{
    protected $signature = 'events:reset_project_timeline
        {project : Project/event ID}
        {--force : Skip confirmation prompt}
        {--keep-images : Keep files referenced by existing timeline items}';

    protected $description = 'Reset a project timeline to the default event-date timeline.';

    public function handle(): int
    {
        $project = Project::query()
            ->withCount('projectTimelineItems')
            ->find($this->argument('project'));

        if (! $project) {
            $this->error('Project not found.');

            return self::FAILURE;
        }

        if (! $project->event_date) {
            $this->error(sprintf('Project #%d does not have an event date.', $project->id));

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Project #%d: %s, event date %s, current timeline items %d.',
            $project->id,
            $project->name,
            $project->event_date->format('Y-m-d'),
            $project->project_timeline_items_count,
        ));

        if (! $this->option('force') && ! $this->confirm('Delete the existing timeline and recreate the default one?')) {
            $this->info('Reset cancelled.');

            return self::SUCCESS;
        }

        $stats = $project->resetDefaultTimelineForEventDate(deleteImages: ! $this->option('keep-images'));

        $this->table(
            ['Project', 'Deleted items', 'Created default items', 'Images'],
            [[
                $project->id,
                $stats['deleted'],
                $stats['created'],
                $this->option('keep-images') ? 'kept' : 'deleted',
            ]]
        );

        return self::SUCCESS;
    }
}
