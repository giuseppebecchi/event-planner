<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectBudgetCategory;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use App\Models\ProjectSupplierCommunication;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use App\Notifications\SupplierCourtesyMessageNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
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

    public function test_courtesy_message_options_include_only_unselected_unsent_suppliers_with_email(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        [$project, $budget, $selectedProposal, $unselectedProposal, $alreadySentProposal, $noEmailProposal] = $this->makeCourtesyMessageScenario();

        ProjectSupplierCommunication::query()->create([
            'project_id' => $project->id,
            'category_budget_supplier_id' => $alreadySentProposal->id,
            'supplier_id' => $alreadySentProposal->supplier_id,
            'communication_type' => 'supplier_courtesy_not_selected',
            'direction' => 'outgoing',
            'communication_at' => now(),
        ]);

        $component = Livewire::test(ManageProjectBudgetCategory::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])->instance();

        $options = $component->getUnselectedSupplierOptions();

        $this->assertArrayHasKey($unselectedProposal->id, $options);
        $this->assertArrayNotHasKey($selectedProposal->id, $options);
        $this->assertArrayNotHasKey($alreadySentProposal->id, $options);
        $this->assertArrayNotHasKey($noEmailProposal->id, $options);
        $this->assertTrue($component->canSendSupplierCourtesyMessages());
    }

    public function test_courtesy_message_sends_mail_and_marks_budget_category(): void
    {
        Notification::fake();

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Template::query()->updateOrCreate(
            ['slug' => 'supplier-courtesy-message', 'language' => 'it'],
            [
                'title' => 'Courtesy IT',
                'subject' => 'Feedback {{ supplier_name }}',
                'type' => Template::TYPE_HTML,
                'content' => '<p>Gentile {{ supplier_name }}</p>',
            ],
        );
        Template::query()->updateOrCreate(
            ['slug' => 'supplier-courtesy-message', 'language' => 'en'],
            [
                'title' => 'Courtesy EN',
                'subject' => 'Feedback {{ supplier_name }}',
                'type' => Template::TYPE_HTML,
                'content' => '<p>Dear {{ supplier_name }}</p>',
            ],
        );

        [$project, $budget, , $unselectedProposal] = $this->makeCourtesyMessageScenario();

        $sentCount = Livewire::test(ManageProjectBudgetCategory::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
        ])->instance()->sendSupplierCourtesyMessages([$unselectedProposal->id]);

        $this->assertSame(1, $sentCount);
        $this->assertTrue((bool) $budget->refresh()->ref_courtesy_messagge_sent_at);
        $this->assertDatabaseHas('project_supplier_communications', [
            'category_budget_supplier_id' => $unselectedProposal->id,
            'supplier_id' => $unselectedProposal->supplier_id,
            'communication_type' => 'supplier_courtesy_not_selected',
        ]);

        Notification::assertSentOnDemand(SupplierCourtesyMessageNotification::class);
    }

    private function makeCourtesyMessageScenario(): array
    {
        $category = Category::query()->firstOrCreate([
            'label' => 'Courtesy category',
            'label_it' => 'Categoria cortesia',
        ]);
        $project = Project::query()->create([
            'name' => 'Courtesy wedding',
            'first_name' => 'Anna',
            'last_name' => 'Rossi',
            'secondary_first_name' => 'Marco',
            'secondary_last_name' => 'Bianchi',
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 1000,
        ]);

        $selectedSupplier = Supplier::query()->create([
            'name' => 'Selected supplier',
            'category_id' => $category->id,
            'email' => 'selected@example.test',
        ]);
        $unselectedSupplier = Supplier::query()->create([
            'name' => 'Unselected supplier',
            'category_id' => $category->id,
            'email' => 'unselected@example.test',
            'lang_comunication' => 'en',
        ]);
        $alreadySentSupplier = Supplier::query()->create([
            'name' => 'Already sent supplier',
            'category_id' => $category->id,
            'email' => 'already-sent@example.test',
        ]);
        $noEmailSupplier = Supplier::query()->create([
            'name' => 'No email supplier',
            'category_id' => $category->id,
        ]);

        $selectedProposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $selectedSupplier->id,
            'responded_at' => now(),
            'availability_status' => 'available',
            'scouting_status' => 'chosen',
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 900,
            'confirmed_at' => now(),
        ]);
        $unselectedProposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $unselectedSupplier->id,
            'responded_at' => now(),
            'availability_status' => 'available',
            'scouting_status' => 'shortlist',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
        ]);
        $alreadySentProposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $alreadySentSupplier->id,
            'responded_at' => now(),
            'availability_status' => 'available',
            'scouting_status' => 'shortlist',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
        ]);
        $noEmailProposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $noEmailSupplier->id,
            'responded_at' => now(),
            'availability_status' => 'available',
            'scouting_status' => 'shortlist',
            'proposal_status' => CategoryBudgetSupplier::STATUS_RECEIVED,
        ]);

        return [$project, $budget, $selectedProposal, $unselectedProposal, $alreadySentProposal, $noEmailProposal];
    }
}
