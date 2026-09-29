<?php

namespace Tests\Feature;

use App\Filament\Pages\LeadStats;
use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class LeadStatsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stats_filter_leads_by_inquiry_year_and_calculate_main_kpis(): void
    {
        $admin = $this->createUser(Role::ADMIN);
        $converted = Lead::query()->create([
            'couple_name' => 'Converted couple',
            'requested_at' => '2036-02-10',
            'source' => 'instagram',
            'status' => 'confirmed',
            'evaluation_outcome' => 'yes',
            'proposal_sent_at' => '2036-02-12 10:00:00',
            'contract_received_at' => '2036-02-20 10:00:00',
            'budget_amount' => 100000,
            'estimated_guest_count' => 80,
            'desired_region' => 'Tuscany',
            'budget_wedding_planner' => [
                ['label' => 'Planning', 'amount' => 12000],
            ],
            'form_sent_at' => '2036-02-10 10:00:00',
            'form_completed_at' => '2036-02-11 10:00:00',
            'form_payload' => [
                'priority_services' => ['Flowers', 'Music'],
                'venue_types' => ['Villa'],
                'discovery_source' => 'Instagram',
            ],
        ]);
        Project::query()->create([
            'lead_id' => $converted->id,
            'name' => 'Converted wedding',
            'last_name' => 'Client',
            'event_date' => '2037-06-01',
        ]);
        Lead::query()->create([
            'couple_name' => 'Open couple',
            'requested_at' => '2036-03-05',
            'source' => 'email',
            'status' => 'new',
            'evaluation_outcome' => 'maybe',
            'budget_amount' => 50000,
        ]);
        Lead::query()->create([
            'couple_name' => 'Previous year',
            'requested_at' => '2035-04-01',
            'source' => 'email',
            'status' => 'lost',
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(LeadStats::class)
            ->set('selectedYear', '2036');
        $stats = $component->instance()->getAnalytics();

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['converted']);
        $this->assertSame(50.0, $stats['conversion_rate']);
        $this->assertSame(75000.0, $stats['budget_average']);
        $this->assertSame(12000.0, $stats['won_fees']);
        $this->assertSame('Flowers', $stats['priorities'][0]['label']);

        $component->call('showAllYears');
        $this->assertGreaterThanOrEqual(3, $component->instance()->getAnalytics()['total']);
    }

    public function test_leads_navigation_is_grouped_into_list_and_stats(): void
    {
        $admin = $this->createUser(Role::ADMIN);
        $this->actingAs($admin);

        $this->assertSame('List', LeadResource::getNavigationLabel());
        $this->assertSame('Stats', LeadStats::getNavigationLabel());
        $this->assertSame('Leads', LeadResource::getNavigationGroup());
        $this->assertSame('Leads', LeadStats::getNavigationGroup());

        $this->get(LeadStats::getUrl())->assertOk();
    }

    public function test_customer_cannot_access_lead_stats(): void
    {
        $customer = $this->createUser(Role::CUSTOMER);
        $customer->forceFill(['customer_portal_welcomed_at' => now()])->save();

        $this->actingAs($customer)
            ->get(LeadStats::getUrl())
            ->assertForbidden();
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
