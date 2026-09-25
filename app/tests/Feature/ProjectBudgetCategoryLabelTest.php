<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectConfirmedSupplier;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectBudget;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectSuppliers;
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

class ProjectBudgetCategoryLabelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_existing_budgets_fall_back_to_the_category_name(): void
    {
        [, $budget] = $this->createBudgetContext();

        $this->assertNull($budget->label);
        $this->assertSame('Music / DJ', $budget->displayLabel());
        $this->assertSame('Musica / DJ', $budget->displayLabel($budget->category->label_it));
    }

    public function test_admin_can_edit_and_reset_the_project_category_label_inline(): void
    {
        [$project, $budget] = $this->createBudgetContext();
        $this->actingAsAdmin();

        $component = Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->assertSee('Music / DJ')
            ->call('startEditingCategoryLabel', $budget->id)
            ->assertSet('editingCategoryLabelBudgetId', $budget->id)
            ->set('editingCategoryLabel', 'Music')
            ->call('saveCategoryLabel', $budget->id)
            ->assertHasNoErrors()
            ->assertSee('Music');

        $this->assertDatabaseHas('category_budgets', [
            'id' => $budget->id,
            'label' => 'Music',
        ]);

        $component
            ->call('startEditingCategoryLabel', $budget->id)
            ->set('editingCategoryLabel', '   ')
            ->call('saveCategoryLabel', $budget->id)
            ->assertHasNoErrors()
            ->assertSee('Music / DJ');

        $this->assertDatabaseHas('category_budgets', [
            'id' => $budget->id,
            'label' => null,
        ]);
    }

    public function test_custom_label_is_used_in_supplier_sections(): void
    {
        [$project, $budget, $proposal] = $this->createBudgetContext(label: 'Music');
        $this->actingAsAdmin();

        Livewire::test(ViewProjectSuppliers::class, ['record' => $project->id])
            ->assertSee('Music')
            ->assertDontSee('Music / DJ');

        $summary = Livewire::test(ManageProjectConfirmedSupplier::class, [
            'record' => $project->id,
            'categoryBudget' => $budget->id,
            'proposal' => $proposal->id,
        ])->instance()->getSummary();

        $this->assertSame('Music', $summary['category']);
    }

    public function test_customer_can_see_but_cannot_edit_the_custom_label(): void
    {
        [$project, $budget] = $this->createBudgetContext(label: 'Music');
        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        $project->users()->attach($customer);
        $this->actingAs($customer);

        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->assertSee('Music')
            ->assertDontSee('Edit Music category label')
            ->call('startEditingCategoryLabel', $budget->id)
            ->assertSet('editingCategoryLabelBudgetId', null);
    }

    protected function createBudgetContext(?string $label = null): array
    {
        $category = Category::query()->create([
            'label' => 'Music / DJ',
            'label_it' => 'Musica / DJ',
        ]);
        $project = Project::query()->create([
            'name' => 'Category label wedding',
            'last_name' => 'Client',
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'label' => $label,
            'initial_estimated_amount' => 1000,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Live band',
            'category_id' => $category->id,
        ]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
            'proposed_amount' => 900,
        ]);

        return [$project, $budget->fresh('category'), $proposal];
    }

    protected function actingAsAdmin(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);

        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));
    }
}
