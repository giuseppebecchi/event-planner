<?php

namespace Tests\Feature;

use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectBudget;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryDefaultSupplierTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        Mail::fake();
        Notification::fake();
    }

    public static function leadAmounts(): array
    {
        return ['positive' => [450, '450.00'], 'explicit zero' => [0, '0.00'], 'missing' => [null, null]];
    }

    #[DataProvider('leadAmounts')]
    public function test_lead_import_confirms_configured_supplier_and_only_explicit_amounts(?int $amount, ?string $expected): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $lead = Lead::query()->create(['couple_name' => 'Default supplier couple', 'budget_vendors' => [
            ['category_id' => $category->id, 'label' => $category->label, 'amount' => $amount],
        ]]);
        $project = $this->project(['lead_id' => $lead->id]);
        $project->initBudget();
        $budget = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail();
        $proposal = $budget->supplierProposals()->sole();
        $this->assertSame($supplier->id, $proposal->supplier_id);
        $this->assertSame('confirmed', $proposal->proposal_status);
        $this->assertSame('chosen', $proposal->scouting_status);
        $this->assertNotNull($proposal->confirmed_at);
        $this->assertSame($expected, $proposal->proposed_amount);
        $this->assertSame('confirmed', $budget->budget_status);
        $this->assertSame($expected, $budget->final_amount);
        $this->assertNull($proposal->requested_at);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_category_added_directly_to_project_confirms_supplier_but_keeps_amount_unknown(): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $project = $this->project();
        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->callAction('addServiceCategory', ['category_id' => $category->id, 'initial_estimated_amount' => 700])
            ->assertHasNoActionErrors();
        $budget = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail();
        $proposal = $budget->supplierProposals()->sole();
        $this->assertSame($supplier->id, $proposal->supplier_id);
        $this->assertSame('confirmed', $proposal->proposal_status);
        $this->assertSame('700.00', $budget->initial_estimated_amount);
        $this->assertNull($proposal->proposed_amount);
        $this->assertNull($budget->final_amount);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_reimport_does_not_duplicate_or_overwrite_a_confirmed_supplier_amount(): void
    {
        [$category] = $this->configuredCategory();
        $lead = Lead::query()->create(['couple_name' => 'Repeat import', 'budget_vendors' => [
            ['category_id' => $category->id, 'amount' => 450],
        ]]);
        $project = $this->project(['lead_id' => $lead->id]);
        $project->initBudget();
        $budget = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail();
        $proposal = $budget->supplierProposals()->sole();
        $proposal->update(['proposed_amount' => 500, 'notes' => 'Agreed manually']);
        $before = $proposal->refresh()->getAttributes();
        $lead->update(['budget_vendors' => [['category_id' => $category->id, 'amount' => 900]]]);
        $project->unsetRelation('lead')->initBudget();
        $this->assertCount(1, $budget->supplierProposals()->get());
        $this->assertSame($before, $proposal->refresh()->getAttributes());
        $this->assertSame('500.00', $budget->refresh()->final_amount);
    }

    public function test_enabling_settings_does_not_link_suppliers_to_existing_budget_items_on_reimport(): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $category->update(['default_supplier_id' => null, 'auto_confirm_default_supplier' => false]);
        $lead = Lead::query()->create(['couple_name' => 'Existing budget couple', 'budget_vendors' => [['category_id' => $category->id, 'amount' => 450]]]);
        $project = $this->project(['lead_id' => $lead->id]);
        $project->initBudget();
        $budget = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail();
        $before = $budget->getAttributes();
        $category->update(['default_supplier_id' => $supplier->id, 'auto_confirm_default_supplier' => true]);
        $project->initBudget();
        $this->assertSame($before, $budget->refresh()->getAttributes());
        $this->assertSame(0, $budget->supplierProposals()->count());
    }

    public function test_default_supplier_can_be_linked_without_automatic_confirmation(): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $category->update(['auto_confirm_default_supplier' => false]);
        $project = $this->project();
        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->callAction('addServiceCategory', ['category_id' => $category->id])->assertHasNoActionErrors();
        $proposal = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail()->supplierProposals()->sole();
        $this->assertSame($supplier->id, $proposal->supplier_id);
        $this->assertSame('requested', $proposal->proposal_status);
        $this->assertNull($proposal->confirmed_at);
        $this->assertNull($proposal->proposed_amount);
        $this->assertNull($proposal->requested_at);
    }

    public function test_deleted_default_supplier_is_not_linked(): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $supplier->delete();
        $project = $this->project();
        Livewire::test(ViewProjectBudget::class, ['record' => $project->id])
            ->callAction('addServiceCategory', ['category_id' => $category->id])->assertHasNoActionErrors();
        $budget = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail();
        $this->assertSame('hypothetical', $budget->budget_status);
        $this->assertSame(0, $budget->supplierProposals()->count());
    }

    public function test_settings_are_editable_and_require_a_supplier_to_auto_confirm(): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $category->update(['default_supplier_id' => null, 'auto_confirm_default_supplier' => false]);
        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['default_supplier_id' => null, 'auto_confirm_default_supplier' => true])
            ->call('save')->assertHasFormErrors(['auto_confirm_default_supplier']);
        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['default_supplier_id' => $supplier->id, 'auto_confirm_default_supplier' => true])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame($supplier->id, $category->refresh()->default_supplier_id);
        $this->assertTrue($category->auto_confirm_default_supplier);
    }

    public function test_configured_planner_category_takes_precedence_over_the_legacy_global_supplier(): void
    {
        [$category, $supplier] = $this->configuredCategory();
        $category->update(['label' => 'Wedding planner', 'label_it' => 'Wedding planner']);
        $legacySupplier = Supplier::query()->create(['name' => 'Legacy global planner', 'category_id' => $category->id]);
        config(['services.default_wedding_id' => $legacySupplier->id]);
        $lead = Lead::query()->create(['couple_name' => 'Planner defaults couple', 'budget_wedding_planner' => [['label' => 'Planning fee', 'amount' => 5000]]]);
        $project = $this->project(['lead_id' => $lead->id]);
        $project->initBudget();
        $project->initBudget();
        $budget = $project->categoryBudgets()->where('category_id', $category->id)->firstOrFail();
        $proposal = $budget->supplierProposals()->sole();
        $this->assertSame($supplier->id, $proposal->supplier_id);
        $this->assertSame('5000.00', $proposal->proposed_amount);
    }

    public function test_existing_supplier_links_are_never_replaced_by_defaults(): void
    {
        [$category] = $this->configuredCategory();
        $budget = $this->project()->categoryBudgets()->create(['category_id' => $category->id]);
        $other = Supplier::query()->create(['name' => 'Already selected supplier', 'category_id' => $category->id]);
        $proposal = $budget->supplierProposals()->create(['supplier_id' => $other->id, 'proposal_status' => 'received', 'proposed_amount' => 350]);
        $before = $proposal->refresh()->getAttributes();
        $this->assertFalse($budget->applyDefaultSupplier(900));
        $this->assertSame($before, $proposal->refresh()->getAttributes());
        $this->assertSame(1, $budget->supplierProposals()->count());
    }

    private function configuredCategory(): array
    {
        $category = Category::query()->create(['label' => 'Configurable automatic supplier', 'label_it' => 'Fornitore automatico', 'order' => 100]);
        $supplier = Supplier::query()->create(['name' => 'Default category supplier', 'category_id' => $category->id]);
        $category->update(['default_supplier_id' => $supplier->id, 'auto_confirm_default_supplier' => true]);

        return [$category, $supplier];
    }

    private function project(array $attributes = []): Project
    {
        return Project::query()->create(['name' => 'Automatic supplier project', 'last_name' => 'Client', ...$attributes]);
    }
}
