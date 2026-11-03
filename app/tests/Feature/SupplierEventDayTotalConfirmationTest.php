<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectSupplier;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierEventDayTotalConfirmationTest extends TestCase
{
    use DatabaseTransactions;

    private function context(?float $amount = 15128): array
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $category = Category::query()->create(['label' => 'Breakdown confirmation', 'label_it' => 'Conferma ripartizione']);
        $project = Project::query()->create([
            'name' => 'Breakdown confirmation project',
            'last_name' => 'Client',
            'event_start_date' => '2027-04-20',
            'event_end_date' => '2027-04-22',
        ]);
        $budget = CategoryBudget::query()->create(['project_id' => $project->id, 'category_id' => $category->id]);
        $supplier = Supplier::query()->create(['name' => 'Confirmed direct price supplier', 'category_id' => $category->id]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposed_amount' => $amount,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
        ]);

        return [$project, $budget, $proposal];
    }

    private function breakdown(Project $project, CategoryBudgetSupplier $proposal, string $first = '7000', string $second = '8000')
    {
        return Livewire::test(ManageProjectSupplier::class, ['record' => $project->id, 'proposal' => $proposal->id])
            ->set('eventDayForm.0.selected', true)
            ->set('eventDayForm.0.amount', $first)
            ->set('eventDayForm.1.selected', true)
            ->set('eventDayForm.1.amount', $second);
    }

    public function test_mismatch_keeps_warning_and_requires_checkbox_and_second_save_before_changing_total(): void
    {
        [$project, $budget, $proposal] = $this->context();
        $component = $this->breakdown($project, $proposal)
            ->call('saveEventDayAllocations')
            ->assertHasErrors(['eventDayForm'])
            ->assertSet('confirmEventDayTotalChange', false)
            ->assertSee('The daily allocation must equal the quote total (EUR 15,128.00).')
            ->assertSee('New total: EUR 15,000.00.')
            ->assertSee('To confirm the total change as well, tick this checkbox and click Save again.');

        $this->assertSame('15128.00', $proposal->refresh()->proposed_amount);
        $this->assertNull($proposal->event_day_allocations);
        $component->call('saveEventDayAllocations')->assertHasErrors(['eventDayForm']);
        $this->assertSame('15128.00', $proposal->refresh()->proposed_amount);

        $component->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')
            ->assertHasNoErrors()
            ->assertSet('confirmEventDayTotalChange', false)
            ->assertSet('eventDayTotalChange', null);
        $this->assertSame('15000.00', $proposal->refresh()->proposed_amount);
        $this->assertSame([
            ['date' => '2027-04-20', 'amount' => 7000],
            ['date' => '2027-04-21', 'amount' => 8000],
        ], $proposal->event_day_allocations);
        $this->assertSame('15000.00', $budget->refresh()->final_amount);
        $this->assertSame(CategoryBudgetSupplier::STATUS_CONFIRMED, $proposal->proposal_status);
        $this->assertSame(15000.0, $component->instance()->getSummary()['confirmed_amount']);
    }

    public function test_empty_quote_requires_confirmation_before_setting_supplier_cost(): void
    {
        [$project, $budget, $proposal] = $this->context(null);
        $component = $this->breakdown($project, $proposal, '500', '700')
            ->call('saveEventDayAllocations')
            ->assertHasErrors(['eventDayForm'])
            ->assertSee('The quote total is empty.')
            ->assertSee('Supplier cost to set: EUR 1,200.00.')
            ->assertSee('To set this amount as the supplier cost, tick this checkbox and click Save again.');
        $this->assertNull($proposal->refresh()->proposed_amount);
        $this->assertNull($proposal->event_day_allocations);

        $component->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')->assertHasNoErrors();
        $this->assertSame('1200.00', $proposal->refresh()->proposed_amount);
        $this->assertSame('1200.00', $budget->refresh()->final_amount);
    }

    public function test_checkbox_cannot_bypass_the_initial_warning(): void
    {
        [$project, , $proposal] = $this->context();
        $this->breakdown($project, $proposal)
            ->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')
            ->assertHasErrors(['eventDayForm'])
            ->assertSet('confirmEventDayTotalChange', false);
        $this->assertSame('15128.00', $proposal->refresh()->proposed_amount);
        $this->assertNull($proposal->event_day_allocations);
    }

    public function test_editing_breakdown_clears_confirmation_and_requires_a_new_warning(): void
    {
        [$project, , $proposal] = $this->context();
        $component = $this->breakdown($project, $proposal)
            ->call('saveEventDayAllocations')
            ->set('confirmEventDayTotalChange', true)
            ->set('eventDayForm.0.amount', '6000')
            ->assertSet('confirmEventDayTotalChange', false)
            ->assertSet('eventDayTotalChange', null)
            ->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')
            ->assertHasErrors(['eventDayForm'])
            ->assertSet('confirmEventDayTotalChange', false)
            ->assertSee('New total: EUR 14,000.00.');
        $this->assertSame('15128.00', $proposal->refresh()->proposed_amount);
        $component->set('confirmEventDayTotalChange', true)->call('saveEventDayAllocations')->assertHasNoErrors();
        $this->assertSame('14000.00', $proposal->refresh()->proposed_amount);
    }

    public function test_concurrent_total_change_requires_new_confirmation(): void
    {
        [$project, , $proposal] = $this->context();
        $component = $this->breakdown($project, $proposal)->call('saveEventDayAllocations');
        $proposal->update(['proposed_amount' => 16000]);
        $component->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')
            ->assertHasErrors(['eventDayForm'])
            ->assertSet('confirmEventDayTotalChange', false)
            ->assertSee('Current total: EUR 16,000.00.');
        $this->assertSame('16000.00', $proposal->refresh()->proposed_amount);
        $this->assertNull($proposal->event_day_allocations);
    }

    public function test_confirmed_total_updates_category_sum_and_commission_but_keeps_other_supplier_and_payments(): void
    {
        [$project, $budget, $proposal] = $this->context();
        $proposal->update(['commission_mode' => CategoryBudgetSupplier::COMMISSION_MODE_PERCENTAGE, 'commission_percentage' => 10]);
        $otherSupplier = Supplier::query()->create(['name' => 'Other confirmed supplier']);
        $other = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $otherSupplier->id,
            'proposed_amount' => 1000,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
        ]);
        $payment = Payment::query()->create([
            'project_id' => $project->id,
            'category_budget_supplier_id' => $proposal->id,
            'supplier_id' => $proposal->supplier_id,
            'reason' => 'Existing deposit',
            'amount' => 500,
            'payment_status' => Payment::STATUS_PAID,
        ]);
        $paymentBefore = $payment->refresh()->getAttributes();
        $component = $this->breakdown($project, $proposal)->call('saveEventDayAllocations')
            ->set('confirmEventDayTotalChange', true)->call('saveEventDayAllocations')->assertHasNoErrors();
        $this->assertSame('16000.00', $budget->refresh()->final_amount);
        $this->assertSame('1000.00', $other->refresh()->proposed_amount);
        $this->assertSame($paymentBefore, $payment->refresh()->getAttributes());
        $this->assertSame('1500.00', $proposal->refresh()->commission_amount);
        $this->assertSame(1500.0, (float) $component->get('commissionForm.commission_amount'));
    }

    public function test_matching_amounts_and_unpriced_day_selection_do_not_require_confirmation(): void
    {
        [$project, , $proposal] = $this->context(0.30);
        $this->breakdown($project, $proposal, '0.10', '0.20')->call('saveEventDayAllocations')
            ->assertHasNoErrors()->assertSet('eventDayTotalChange', null);
        $this->assertSame('0.30', $proposal->refresh()->proposed_amount);

        [$project, , $proposal] = $this->context(null);
        $this->breakdown($project, $proposal, '', '')->call('saveEventDayAllocations')
            ->assertHasNoErrors()->assertSet('eventDayTotalChange', null);
        $this->assertNull($proposal->refresh()->proposed_amount);
        $this->assertSame([
            ['date' => '2027-04-20', 'amount' => null],
            ['date' => '2027-04-21', 'amount' => null],
        ], $proposal->event_day_allocations);
    }

    public function test_invalid_or_incomplete_daily_amounts_cannot_be_confirmed(): void
    {
        [$project, , $proposal] = $this->context();
        $this->breakdown($project, $proposal, '500', '')
            ->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')
            ->assertHasErrors(['eventDayForm'])
            ->assertSet('eventDayTotalChange', null);
        $this->assertSame('15128.00', $proposal->refresh()->proposed_amount);
        $this->assertNull($proposal->event_day_allocations);
    }

    public function test_customer_cannot_change_breakdown_or_supplier_cost(): void
    {
        [$project, , $proposal] = $this->context();
        $role = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $role->id]);
        $project->users()->attach($customer);
        $this->actingAs($customer);
        $this->breakdown($project, $proposal)->set('confirmEventDayTotalChange', true)
            ->call('saveEventDayAllocations')->assertStatus(403);
        $this->assertSame('15128.00', $proposal->refresh()->proposed_amount);
        $this->assertNull($proposal->event_day_allocations);
    }
}
