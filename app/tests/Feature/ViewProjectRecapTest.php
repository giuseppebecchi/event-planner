<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectRecap;
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

class ViewProjectRecapTest extends TestCase
{
    use DatabaseTransactions;

    public function test_recap_excludes_confirmed_siae_supplier(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $project = Project::query()->create([
            'name' => 'Recap wedding',
            'last_name' => 'Client',
        ]);

        $siaeCategory = Category::query()->create(['label' => 'SIAE', 'label_it' => 'SIAE']);
        $flowersCategory = Category::query()->create(['label' => 'Flowers', 'label_it' => 'Fiori']);

        $siaeBudget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $siaeCategory->id,
        ]);
        $flowersBudget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $flowersCategory->id,
        ]);

        $siaeSupplier = Supplier::query()->create([
            'name' => 'SIAE Supplier',
            'category_id' => $siaeCategory->id,
        ]);
        $flowersSupplier = Supplier::query()->create([
            'name' => 'Flower Supplier',
            'category_id' => $flowersCategory->id,
        ]);

        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $siaeBudget->id,
            'supplier_id' => $siaeSupplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
        ]);
        CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $flowersBudget->id,
            'supplier_id' => $flowersSupplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ViewProjectRecap::class, [
            'record' => $project->id,
        ]);

        $confirmedSuppliers = $component->instance()->getRecapConfirmedSuppliers();

        $this->assertCount(1, $confirmedSuppliers);
        $this->assertSame('Flower Supplier', $confirmedSuppliers->first()['name']);

        $component
            ->assertSee('Flower Supplier')
            ->assertDontSee('SIAE Supplier');
    }
}
