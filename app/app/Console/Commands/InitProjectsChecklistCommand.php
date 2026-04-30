<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class InitProjectsChecklistCommand extends Command
{
    protected $signature = 'events:init_projects_checklist';

    protected $description = 'Instantiate checklist options for existing projects that do not have them yet.';

    public function handle(): int
    {
        $projects = Project::query()
            ->whereDoesntHave('projectChecklistOptions')
            ->orderBy('id')
            ->get();

        if ($projects->isEmpty()) {
            $this->info('All projects already have checklist options.');

            return self::SUCCESS;
        }

        $processedProjects = 0;
        $createdOptions = 0;

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        foreach ($projects as $project) {
            $beforeCount = (int) $project->projectChecklistOptions()->count();

            $project->syncChecklistOptionsFromTemplates();

            $afterCount = (int) $project->projectChecklistOptions()->count();

            $processedProjects++;
            $createdOptions += max(0, $afterCount - $beforeCount);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Projects updated', 'Checklist options created'],
            [[
                $processedProjects,
                $createdOptions,
            ]]
        );

        return self::SUCCESS;
    }
}
