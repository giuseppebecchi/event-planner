<?php

namespace Tests\Feature;

use App\Filament\Resources\CommissionResource;
use App\Filament\Resources\CommissionResource\Pages\ListCommissions;
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

class CommissionResourceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_internal_user_sees_paid_and_unpaid_commissions_for_past_events_only(): void
    {
        $admin = $this->createUser(Role::ADMIN);
        $unpaid = $this->createCommission('Past unpaid event', today()->subDay(), 500, []);
        $paid = $this->createCommission('Past paid event', today()->subDays(2), 300, [
            ['amount' => 300, 'paid_at' => today()->subDay()->toDateString()],
        ]);
        $future = $this->createCommission('Future event', today()->addDay(), 200, []);

        $this->actingAs($admin);

        Livewire::test(ListCommissions::class)
            ->assertCanSeeTableRecords([$unpaid, $paid])
            ->assertCanNotSeeTableRecords([$future]);
    }

    public function test_commission_can_be_marked_as_paid_without_losing_a_partial_payment(): void
    {
        $admin = $this->createUser(Role::ADMIN);
        $commission = $this->createCommission('Past event', today()->subDay(), 500, [
            ['amount' => 125, 'paid_at' => today()->subDays(2)->toDateString()],
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCommissions::class)
            ->callTableAction('markPaid', $commission);

        $commission->refresh();

        $this->assertTrue($commission->isCommissionPaid());
        $this->assertSame(500.0, (float) $commission->commission_total_amount_payed);
        $this->assertCount(2, $commission->commission_payments_json);
        $this->assertSame(375.0, (float) $commission->commission_payments_json[1]['amount']);
        $this->assertSame(today()->toDateString(), $commission->commission_payments_json[1]['paid_at']);
    }

    public function test_customer_cannot_access_commissions_resource(): void
    {
        $customer = $this->createUser(Role::CUSTOMER);
        $customer->forceFill(['customer_portal_welcomed_at' => now()])->save();

        $this->actingAs($customer)
            ->get(CommissionResource::getUrl())
            ->assertForbidden();
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createCommission(string $projectName, mixed $eventDate, float $amount, array $payments): CategoryBudgetSupplier
    {
        $project = Project::query()->create([
            'name' => $projectName,
            'last_name' => 'Client',
            'event_date' => $eventDate,
        ]);
        $category = Category::query()->create([
            'label' => 'Music '.uniqid(),
            'label_it' => 'Musica '.uniqid(),
        ]);
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Supplier '.uniqid(),
            'category_id' => $category->id,
        ]);

        return CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'commission_mode' => CategoryBudgetSupplier::COMMISSION_MODE_FIXED,
            'commission_amount' => $amount,
            'commission_payments_json' => $payments,
        ]);
    }
}
