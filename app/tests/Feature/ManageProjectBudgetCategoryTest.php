<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectBudgetCategory;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ManageProjectBudgetCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_chosen_quote_can_be_moved_back_to_another_scouting_status(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $category = Category::query()->firstOrCreate([
            'label' => 'Test category',
            'label_it' => 'Categoria test',
        ]);
        $project = Project::query()->create([
            'name' => 'Test wedding',
            'last_name' => 'Partner one',
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 1000,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Test supplier',
            'category_id' => $category->id,
        ]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'responded_at' => now(),
            'availability_status' => 'available',
            'scouting_status' => 'chosen',
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 900,
            'confirmed_at' => now(),
        ]);

        Livewire::test(ManageProjectBudgetCategory::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->call('openRecordResponseModal', $proposal->id)
            ->set('responseForm.scouting_status', 'shortlist')
            ->call('saveRecordResponse')
            ->assertHasNoErrors();

        $proposal->refresh();

        $this->assertSame('shortlist', $proposal->scouting_status);
        $this->assertSame(CategoryBudgetSupplier::STATUS_RECEIVED, $proposal->proposal_status);
        $this->assertNull($proposal->confirmed_at);
    }

    public function test_quote_comparison_total_uses_cost_item_sum_instead_of_proposed_amount(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $category = Category::query()->firstOrCreate([
            'label' => 'Test category',
            'label_it' => 'Categoria test',
        ]);
        $project = Project::query()->create([
            'name' => 'Test wedding comparison',
            'last_name' => 'Partner one',
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 1000,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Comparison supplier',
            'category_id' => $category->id,
        ]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'responded_at' => now(),
            'availability_status' => 'available',
            'scouting_status' => 'shortlist',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
            'proposed_amount' => 999,
            'cost_items_json' => [
                ['label' => 'Food', 'amount' => 100],
                ['label' => 'Service', 'amount' => 25.5],
            ],
        ]);

        $comparison = Livewire::test(ManageProjectBudgetCategory::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])->instance()->getProposalComparison();

        $this->assertSame(125.5, $comparison['totals'][$proposal->id]);
    }
}
