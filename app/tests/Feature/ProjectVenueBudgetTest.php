<?php

namespace Tests\Feature;

use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectVenueBudgetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_project_budget_initialization_confirms_selected_venue_supplier(): void
    {
        $venue = $this->createVenue();
        $project = Project::query()->create([
            'name' => 'Wedding with venue',
            'last_name' => 'Client',
            'venue_id' => $venue->id,
        ]);

        $project->initBudget();

        $budget = CategoryBudget::query()
            ->where('project_id', $project->id)
            ->where('category_id', Supplier::LOCATION_CATEGORY_ID)
            ->first();

        $this->assertNotNull($budget);
        $this->assertSame(CategoryBudget::STATUS_CONFIRMED, $budget->budget_status);

        $proposal = CategoryBudgetSupplier::query()
            ->where('category_budget_id', $budget->id)
            ->where('supplier_id', $venue->id)
            ->first();

        $this->assertNotNull($proposal);
        $this->assertSame(CategoryBudgetSupplier::STATUS_CONFIRMED, $proposal->proposal_status);
        $this->assertSame('chosen', $proposal->scouting_status);
        $this->assertNotNull($proposal->confirmed_at);
    }

    public function test_project_generated_from_lead_confirms_copied_venue_supplier(): void
    {
        $venue = $this->createVenue();
        $lead = Lead::query()->create([
            'couple_name' => 'Lead Couple',
            'venue_id' => $venue->id,
        ]);
        $project = Project::query()->create([
            'lead_id' => $lead->id,
            'name' => 'Generated wedding',
            'last_name' => 'Client',
            'venue_id' => $lead->venue_id,
        ]);

        $project->loadMissing('lead')->initBudget();

        $this->assertDatabaseHas('category_budget_suppliers', [
            'project_id' => $project->id,
            'category_id' => Supplier::LOCATION_CATEGORY_ID,
            'supplier_id' => $venue->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
        ]);
    }

    protected function createVenue(): Supplier
    {
        DB::table('categories')->updateOrInsert(
            ['id' => Supplier::LOCATION_CATEGORY_ID],
            [
                'label' => 'Venue',
                'label_it' => 'Venue',
                'main' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );

        return Supplier::query()->create([
            'name' => 'Confirmed venue',
            'category_id' => Supplier::LOCATION_CATEGORY_ID,
            'loc_rental_fee' => 12000,
        ]);
    }
}
