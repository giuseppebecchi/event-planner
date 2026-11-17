<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\ProposalTemplate;
use App\Support\ProposalConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NormalizeVisualProposalConditions extends Command
{
    protected $signature = 'proposal-templates:normalize-conditions {--apply : Back up and update visual proposals and templates}';

    protected $description = 'Remove legacy-only conditions headings and join printed text into one editable block; legacy leads are excluded';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $templates = ProposalTemplate::query()->lockForUpdate()->get()
                ->filter(fn ($template) => ($template->configuration['conditions_format'] ?? null) !== 'rich-text');
            $leads = Lead::whereNotNull('proposal_layout')->lockForUpdate()->get()
                ->filter(fn ($lead) => ($lead->proposal_layout['conditions_format'] ?? null) !== 'rich-text');
            $this->info("Templates: {$templates->count()}; visual proposals: {$leads->count()}. Legacy proposals excluded.");
            if (! $this->option('apply') || ($templates->isEmpty() && $leads->isEmpty())) {
                return;
            }

            $backup = 'proposal-conditions-backups/'.now()->format('Ymd-His-u').'.json';
            if (! Storage::disk('local')->put($backup, json_encode([
                'templates' => $templates->map->only(['id', 'configuration'])->values()->all(),
                'leads' => $leads->map->only(['id', 'proposal_content', 'proposal_layout'])->values()->all(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                throw new \RuntimeException('Cannot save the original text backup. No records updated.');
            }
            foreach ($templates as $template) {
                $config = $template->configuration;
                $config['conditions'] = ProposalConfiguration::conditionsFromLegacy($config['conditions'] ?? '');
                $config['conditions_format'] = 'rich-text';
                $template->update(['configuration' => $config]);
            }
            foreach ($leads as $lead) {
                $layout = $lead->proposal_layout;
                $layout['conditions_format'] = 'rich-text';
                $lead->update(['proposal_content' => ProposalConfiguration::conditionsFromLegacy($lead->proposal_content ?? ''),
                    'proposal_layout' => $layout]);
            }
            $this->info('Original text backup: '.Storage::disk('local')->path($backup));
        });

        return self::SUCCESS;
    }
}
