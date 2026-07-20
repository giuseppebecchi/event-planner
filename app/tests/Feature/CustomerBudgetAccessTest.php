<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectConfirmedSupplier;
use App\Filament\Resources\ProjectResource\Pages\ManageProjectBudgetCategory;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectBudget;
use App\Filament\Resources\ProjectResource;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerBudgetAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_budget_rows_include_all_categories_and_supplier_proposals(): void
    {
        [$customer, $project, $budget] = $this->createCustomerBudgetContext();

        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => Supplier::query()->create(['name' => 'Shortlist supplier', 'category_id' => $budget->category_id])->id,
            'scouting_status' => 'shortlist',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
            'proposed_amount' => 1000,
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => Supplier::query()->create(['name' => 'Chosen supplier', 'category_id' => $budget->category_id])->id,
            'scouting_status' => 'chosen',
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 900,
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => Supplier::query()->create(['name' => 'Discarded supplier', 'category_id' => $budget->category_id])->id,
            'scouting_status' => 'discarded',
            'proposal_status' => 'discarded',
            'proposed_amount' => 1200,
        ]);

        $emptyCategory = Category::query()->create(['label' => 'Empty category', 'label_it' => 'Categoria vuota']);
        CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $emptyCategory->id,
            'initial_estimated_amount' => 500,
        ]);

        $this->actingAs($customer);

        $rows = Livewire::test(ViewProjectBudget::class, [
            'record' => $project->id,
        ])->instance()->getBudgetRows();

        $this->assertCount(2, $rows);
        $this->assertCount(3, $rows->firstWhere('id', $budget->id)->supplierProposals);
    }

    public function test_customer_can_open_any_supplier_proposal_documents_without_commissions(): void
    {
        [$customer, $project, $budget] = $this->createCustomerBudgetContext();
        $supplier = Supplier::query()->create(['name' => 'Received supplier', 'category_id' => $budget->category_id]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'scouting_status' => 'discarded',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
            'proposed_amount' => 1200,
            'commission_amount' => 300,
        ]);

        ProjectDocument::query()->create([
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'category_budget_supplier_id' => $proposal->id,
            'title' => 'Supplier quote',
            'document_type' => ProjectDocument::TYPE_QUOTE,
            'type' => ProjectDocument::TYPE_QUOTE,
            'file_path' => 'projects/documents/quote.pdf',
        ]);

        $this->actingAs($customer);

        $component = Livewire::withQueryParams(['proposal' => $proposal->id])
            ->test(ManageProjectConfirmedSupplier::class, [
                'record' => $project->id,
                'categoryBudget' => $budget->id,
            ]);

        $this->assertSame($proposal->id, $component->instance()->proposalRecord->id);
        $this->assertCount(1, $component->instance()->getDocumentsByType(ProjectDocument::TYPE_QUOTE));
        $this->assertNotContains('commissions', collect($component->instance()->getDashboardCards())->pluck('key')->all());
    }

    public function test_customer_open_details_uses_budget_scouting_page_read_only(): void
    {
        [$customer, $project, $budget] = $this->createCustomerBudgetContext();
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => Supplier::query()->create(['name' => 'Visible supplier', 'category_id' => $budget->category_id])->id,
            'scouting_status' => 'chosen',
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 1000,
        ]);

        $this->actingAs($customer);

        Livewire::test(ViewProjectBudget::class, [
            'record' => $project->id,
        ])
            ->assertSee(ProjectResource::getUrl('budget-scouting', [
                'record' => $project,
                'categoryBudget' => $budget,
            ]))
            ->assertDontSee(ProjectResource::getUrl('budget-manage', [
                'record' => $project,
                'categoryBudget' => $budget,
            ]));

        Livewire::test(ManageProjectBudgetCategory::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])
            ->assertSee('Visible supplier')
            ->assertDontSee('Supplier search')
            ->assertDontSee('Update quote / response')
            ->call('openAcceptProposalModal', $budget->supplierProposals()->first()->id)
            ->assertForbidden();
    }

    public function test_budget_summary_totals_include_all_rows_even_unconfirmed_and_venue(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $project = Project::query()->create([
            'name' => 'Full totals wedding',
            'last_name' => 'Client',
            'venue_included_in_budget' => false,
        ]);

        $venue = Category::query()->create(['label' => 'Venue', 'label_it' => 'Location']);
        $flowers = Category::query()->create(['label' => 'Flowers', 'label_it' => 'Fiori']);
        $music = Category::query()->create(['label' => 'Music', 'label_it' => 'Musica']);

        CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $venue->id,
            'initial_estimated_amount' => 1000,
            'final_amount' => 900,
            'budget_status' => CategoryBudget::STATUS_CONFIRMED,
        ]);
        CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $flowers->id,
            'initial_estimated_amount' => 500,
            'budget_status' => CategoryBudget::STATUS_HYPOTHETICAL,
        ]);
        CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $music->id,
            'initial_estimated_amount' => 700,
            'comparison_amount' => 660,
            'final_amount' => 650,
            'budget_status' => CategoryBudget::STATUS_IN_EVALUATION,
        ]);

        $this->actingAs($admin);

        $summary = Livewire::test(ViewProjectBudget::class, [
            'record' => $project->id,
        ])->instance()->getBudgetSummary();

        $this->assertSame(3, $summary['categories_count']);
        $this->assertFalse($summary['venue_excluded']);
        $this->assertSame(2200.0, $summary['estimated_total']);
        $this->assertSame(2160.0, $summary['comparison_total']);
        $this->assertSame(1550.0, $summary['final_total']);
        $this->assertSame(2200.0, $summary['confirmed_hypothetical_total']);
    }

    protected function createCustomerBudgetContext(): array
    {
        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        $project = Project::query()->create([
            'name' => 'Customer budget wedding',
            'last_name' => 'Client',
        ]);
        $project->users()->attach($customer);

        $category = Category::query()->create(['label' => 'Flowers', 'label_it' => 'Fiori']);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 1000,
        ]);

        return [$customer, $project, $budget];
    }
}
