<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectBudget;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Checklist;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectChecklistOption;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectBudgetDeletionTest extends TestCase
{
    use DatabaseTransactions;

    private function context(): array
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $project = Project::query()->create(['name' => 'Budget deletion wedding', 'last_name' => 'Client']);
        $category = Category::query()->create(['label' => 'SIAE deletion test', 'label_it' => 'SIAE']);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 0,
            'budget_status' => CategoryBudget::STATUS_HYPOTHETICAL,
        ]);

        return [$project, $category, $budget];
    }

    public function test_budget_row_requires_confirmation_and_cancel_preserves_it(): void
    {
        [$project, , $budget] = $this->context();
        $component = Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->assertSee('Delete SIAE deletion test budget item')
            ->mountAction('deleteBudgetCategory', ['budget' => $budget->id])
            ->assertActionMounted('deleteBudgetCategory');

        $this->assertSame('Delete SIAE deletion test from this budget?', $component->instance()->getMountedAction()->getModalHeading());

        $this->assertDatabaseHas('category_budgets', ['id' => $budget->id]);
        $component->call('unmountAction');
        $this->assertDatabaseHas('category_budgets', ['id' => $budget->id]);
    }

    public function test_delete_removes_only_current_project_row_and_category_can_be_added_again(): void
    {
        [$project, $category, $budget] = $this->context();
        $otherProject = Project::query()->create(['name' => 'Other wedding', 'last_name' => 'Client']);
        $otherBudget = CategoryBudget::query()->create(['project_id' => $otherProject->id, 'category_id' => $category->id, 'initial_estimated_amount' => 500]);
        $remainingCategory = Category::query()->create(['label' => 'Remaining budget category', 'label_it' => 'Voce mantenuta']);
        $remaining = CategoryBudget::query()->create(['project_id' => $project->id, 'category_id' => $remainingCategory->id, 'initial_estimated_amount' => 5000]);

        $component = Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->call('startEditingCategoryLabel', $budget->id)
            ->mountAction('deleteBudgetCategory', ['budget' => $budget->id])
            ->callMountedAction()
            ->assertHasNoErrors()
            ->assertSet('editingCategoryLabelBudgetId', null)
            ->assertDontSee('SIAE deletion test');

        $this->assertDatabaseMissing('category_budgets', ['id' => $budget->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('category_budgets', ['id' => $otherBudget->id]);
        $this->assertDatabaseHas('category_budgets', ['id' => $remaining->id]);
        $this->assertSame(1, $component->instance()->getBudgetSummary()['categories_count']);
        $this->assertSame(5000.0, $component->instance()->getBudgetSummary()['estimated_total']);

        $component->callAction('addServiceCategory', ['category_id' => $category->id, 'initial_estimated_amount' => 0])
            ->assertHasNoErrors();
        $this->assertDatabaseHas('category_budgets', ['project_id' => $project->id, 'category_id' => $category->id]);
    }

    public function test_supplier_requests_and_related_payments_are_protected_even_at_zero_price(): void
    {
        [$project, $category, $budget] = $this->context();
        $supplier = Supplier::query()->create(['name' => 'Linked budget supplier', 'category_id' => $category->id]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposed_amount' => 0,
            'proposal_status' => CategoryBudgetSupplier::STATUS_REQUESTED,
        ]);
        $payment = Payment::query()->create([
            'project_id' => $project->id,
            'category_budget_supplier_id' => $proposal->id,
            'supplier_id' => $supplier->id,
            'reason' => 'Protected payment',
            'amount' => 100,
            'payment_status' => Payment::STATUS_UNPAID,
        ]);

        // Calling the action directly must still enforce the check behind the disabled UI button.
        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->callAction('deleteBudgetCategory', arguments: ['budget' => $budget->id])
            ->assertNotified('Budget item cannot be deleted');
        $this->assertDatabaseHas('category_budgets', ['id' => $budget->id]);
        $this->assertDatabaseHas('category_budget_suppliers', ['id' => $proposal->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_new_supplier_link_added_after_opening_confirmation_prevents_deletion(): void
    {
        [$project, , $budget] = $this->context();
        $component = Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->mountAction('deleteBudgetCategory', ['budget' => $budget->id]);
        $supplier = Supplier::query()->create(['name' => 'Concurrent supplier']);
        $proposal = CategoryBudgetSupplier::query()->create(['category_budget_id' => $budget->id, 'supplier_id' => $supplier->id]);

        $component->callMountedAction()->assertNotified('Budget item cannot be deleted');
        $this->assertDatabaseHas('category_budgets', ['id' => $budget->id]);
        $this->assertDatabaseHas('category_budget_suppliers', ['id' => $proposal->id]);
    }

    public function test_existing_checklist_tasks_are_preserved_when_the_empty_budget_is_deleted(): void
    {
        [$project, , $budget] = $this->context();
        $checklist = Checklist::query()->create(['title' => 'Checklist to keep', 'options' => []]);
        $task = ProjectChecklistOption::query()->create([
            'project_id' => $project->id,
            'category_budget_id' => $budget->id,
            'checkbox_id' => $checklist->id,
            'order' => 1,
            'title' => 'Keep this task',
            'enabled' => true,
        ]);

        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->callAction('deleteBudgetCategory', arguments: ['budget' => $budget->id])
            ->assertHasNoErrors();
        $this->assertDatabaseMissing('category_budgets', ['id' => $budget->id]);
        $this->assertDatabaseHas('project_checklist_options', ['id' => $task->id, 'title' => 'Keep this task', 'category_budget_id' => null]);
    }

    public function test_customer_cannot_see_or_execute_budget_deletion(): void
    {
        [$project, , $budget] = $this->context();
        $role = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $role->id]);
        $project->users()->attach($customer);
        $this->actingAs($customer);

        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->assertDontSee('Delete SIAE deletion test budget item')
            ->assertActionHidden('deleteBudgetCategory')
            ->call('mountAction', 'deleteBudgetCategory', ['budget' => $budget->id])
            ->callMountedAction();
        $this->assertDatabaseHas('category_budgets', ['id' => $budget->id]);
    }

    public function test_budget_from_another_project_cannot_be_deleted(): void
    {
        [$project] = $this->context();
        [, , $otherBudget] = $this->context();
        $this->assertDatabaseHas('category_budgets', ['id' => $otherBudget->id]);
        $this->expectException(ModelNotFoundException::class);
        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->call('mountAction', 'deleteBudgetCategory', ['budget' => $otherBudget->id]);
    }
}
