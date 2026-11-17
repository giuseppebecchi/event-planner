<?php

namespace App\Console\Commands;

use App\Models\ProposalTemplate;
use App\Support\ProposalConfiguration;
use Illuminate\Console\Command;

class ImportProposalTemplates extends Command
{
    protected $signature = 'proposal-templates:import-defaults';

    protected $description = 'Import the current Tuscany and Lake Como proposal defaults without changing leads or existing templates';

    public function handle(): int
    {
        foreach (['tuscany' => 'Tuscany', 'lake_como' => 'Lake Como'] as $slug => $name) {
            ProposalTemplate::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true, 'configuration' => ProposalConfiguration::defaults($slug)]);
        }
        $this->info('Proposal templates imported. Existing templates and leads were not modified.');

        return self::SUCCESS;
    }
}
