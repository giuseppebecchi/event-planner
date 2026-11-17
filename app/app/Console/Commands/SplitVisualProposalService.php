<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\ProposalTemplate;
use App\Support\ProposalConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SplitVisualProposalService extends Command
{
    protected $signature = 'proposal-templates:split-service {--apply : Back up and split the existing visual service columns}';

    protected $description = 'Split visual planning service text at its current midpoint without modifying legacy proposals';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $templates = ProposalTemplate::query()->lockForUpdate()->get()
                ->filter(fn ($template) => ! array_key_exists('planning_service_right', $template->configuration));
            $leads = Lead::whereNotNull('proposal_layout')->lockForUpdate()->get()
                ->filter(fn ($lead) => ! array_key_exists('planning_service_right', $lead->proposal_layout));
            $this->info("Templates: {$templates->count()}; visual proposals: {$leads->count()}. Legacy proposals excluded.");
            if (! $this->option('apply') || ($templates->isEmpty() && $leads->isEmpty())) {
                return;
            }
            $backup = 'proposal-service-backups/'.now()->format('Ymd-His-u').'.json';
            if (! Storage::disk('local')->put($backup, json_encode([
                'templates' => $templates->map->only(['id', 'configuration'])->values()->all(),
                'leads' => $leads->map->only(['id', 'proposal_wedding_planning_service', 'proposal_layout'])->values()->all(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                throw new \RuntimeException('Cannot back up the original service text. No records updated.');
            }
            foreach ($templates as $template) {
                $template->update(['configuration' => [...$template->configuration,
                    ...ProposalConfiguration::splitPlanningService($template->configuration['planning_service'] ?? '')]]);
            }
            foreach ($leads as $lead) {
                $columns = ProposalConfiguration::splitPlanningService($lead->proposal_wedding_planning_service ?? '');
                $lead->update(['proposal_wedding_planning_service' => $columns['planning_service'],
                    'proposal_layout' => [...$lead->proposal_layout, 'planning_service_right' => $columns['planning_service_right']]]);
            }
            $this->info('Original text backup: '.Storage::disk('local')->path($backup));
        });

        return self::SUCCESS;
    }
}
