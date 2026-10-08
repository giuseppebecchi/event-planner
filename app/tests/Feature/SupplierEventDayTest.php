<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectSupplier;
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

class SupplierEventDayTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_manage_event_days_from_supplier_header_and_customer_can_view_them(): void
    {
        $category = Category::query()->firstOrCreate(
            ['label' => 'Supplier event days'],
            ['label_it' => 'Giorni fornitore'],
        );
        $project = Project::query()->create([
            'name' => 'Supplier event days project',
            'last_name' => 'Client',
            'event_date' => '2027-09-03',
            'event_start_date' => '2027-09-03',
            'event_end_date' => '2027-09-04',
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Event days supplier',
            'category_id' => $category->id,
        ]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
            'proposed_amount' => 1200,
        ]);

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Livewire::test(ManageProjectSupplier::class, [
            'record' => $project->id,
            'proposal' => $proposal->id,
        ])
            ->assertSee('Event days')
            ->set('eventDayForm.0.selected', true)
            ->set('eventDayForm.0.amount', '500')
            ->set('eventDayForm.1.selected', true)
            ->set('eventDayForm.1.amount', '700')
            ->call('saveEventDayAllocations')
            ->assertHasNoErrors();

        $this->assertSame([
            ['date' => '2027-09-03', 'amount' => 500],
            ['date' => '2027-09-04', 'amount' => 700],
        ], $proposal->refresh()->event_day_allocations);

        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        $project->users()->attach($customer);
        $this->actingAs($customer);

        Livewire::test(ManageProjectSupplier::class, [
            'record' => $project->id,
            'proposal' => $proposal->id,
        ])
            ->assertSee('Event days')
            ->assertSee('EUR 500,00')
            ->assertSee('EUR 700,00');
    }

    public function test_multi_day_supplier_list_shows_days_and_filters_including_unassigned_suppliers(): void
    {
        $category = Category::query()->firstOrCreate(
            ['label' => 'Supplier list event days'],
            ['label_it' => 'Giorni elenco fornitori'],
        );
        $project = Project::query()->create([
            'name' => 'Filtered supplier event',
            'last_name' => 'Client',
            'event_date' => '2027-10-24',
            'event_start_date' => '2027-10-24',
            'event_end_date' => '2027-10-26',
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);

        $firstDaySupplier = Supplier::query()->create(['name' => 'First day supplier', 'category_id' => $category->id]);
        $secondDaySupplier = Supplier::query()->create(['name' => 'Second day supplier', 'category_id' => $category->id]);
        $unassignedSupplier = Supplier::query()->create(['name' => 'Unassigned supplier', 'category_id' => $category->id]);

        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $firstDaySupplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'event_day_allocations' => [['date' => '2027-10-24', 'amount' => null]],
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $secondDaySupplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'event_day_allocations' => [['date' => '2027-10-25', 'amount' => null]],
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $unassignedSupplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
        ]);

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Livewire::test(ViewProjectSuppliers::class, ['record' => $project->id])
            ->assertSee('Filter suppliers by event day')
            ->assertSee('24 October')
            ->assertSee('25 October')
            ->assertSee('26 October')
            ->assertSee('Days')
            ->assertSee('24 Oct')
            ->set('supplierDayFilter', '2027-10-24')
            ->assertSee('First day supplier')
            ->assertSee('Unassigned supplier')
            ->assertDontSee('Second day supplier')
            ->set('supplierDayFilter', 'all')
            ->assertSee('Second day supplier');
    }

    public function test_budget_recap_shows_allocated_supplier_costs_grouped_by_event_day(): void
    {
        $category = Category::query()->firstOrCreate(
            ['label' => 'Daily budget category'],
            ['label_it' => 'Categoria budget giornaliero'],
        );
        $project = Project::query()->create([
            'name' => 'Daily budget event',
            'last_name' => 'Client',
            'event_date' => '2027-11-14',
            'event_start_date' => '2027-11-14',
            'event_end_date' => '2027-11-15',
            'venue_included_in_budget' => true,
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'initial_estimated_amount' => 1500,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Daily budget supplier',
            'category_id' => $category->id,
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 1500,
            'event_day_allocations' => [
                ['date' => '2027-11-14', 'amount' => 1000],
                ['date' => '2027-11-15', 'amount' => 500],
            ],
        ]);
        $unallocatedSupplier = Supplier::query()->create([
            'name' => 'Whole event supplier',
            'category_id' => $category->id,
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $unallocatedSupplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => 250,
        ]);

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        $component = Livewire::test(ViewProjectBudget::class, ['record' => $project->id]);
        $breakdown = $component->instance()->getBudgetDayBreakdown();

        $this->assertSame(1000.0, $breakdown->first()['total']);
        $this->assertSame(500.0, $breakdown->last()['total']);

        $component
            ->assertSee('Budget by event day')
            ->assertSee('Sunday 14 November 2027')
            ->assertSee('Monday 15 November 2027')
            ->assertSee('EUR 1.000,00')
            ->assertSee('EUR 500,00')
            ->assertSee('Whole-event cost (not allocated)')
            ->assertSee('EUR 250,00');
    }
}
