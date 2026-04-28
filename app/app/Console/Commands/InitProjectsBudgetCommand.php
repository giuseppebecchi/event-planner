<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class InitProjectsBudgetCommand extends Command
{
    protected $signature = 'events:init_projects_budget';

    protected $description = 'Initialize category budgets for all existing projects based on their lead estimates.';

    public function handle(): int
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $withoutLead = 0;

        $projects = Project::query()
            ->with('lead')
            ->orderBy('id')
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No projects found.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        foreach ($projects as $project) {
            $stats = $project->initBudget();

            $created += $stats['created'];
            $updated += $stats['updated'];
            $skipped += $stats['skipped'];
            $withoutLead += $stats['no_lead'] ? 1 : 0;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Projects', 'Without lead', 'Created budgets', 'Updated budgets', 'Skipped rows'],
            [[
                $projects->count(),
                $withoutLead,
                $created,
                $updated,
                $skipped,
            ]]
        );

        return self::SUCCESS;
    }
}
