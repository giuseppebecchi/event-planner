<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar as MasterCalendar;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class MasterCalendarAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_only_sees_calendar_items_for_assigned_projects(): void
    {
        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        $assignedProject = Project::query()->create([
            'name' => 'Assigned wedding',
            'last_name' => 'Client',
            'status' => 'confirmed',
        ]);
        $hiddenProject = Project::query()->create([
            'name' => 'Other wedding',
            'last_name' => 'Other',
            'status' => 'confirmed',
        ]);

        $customer->projects()->attach($assignedProject);

        $assignedProject->projectEvents()->create([
            'title' => 'Assigned appointment',
            'starts_at' => '2026-07-22 00:00:00',
            'ends_at' => '2026-07-22 23:59:59',
            'is_all_day' => true,
        ]);
        $hiddenProject->projectEvents()->create([
            'title' => 'Private admin appointment',
            'starts_at' => '2026-07-23 00:00:00',
            'ends_at' => '2026-07-23 23:59:59',
            'is_all_day' => true,
        ]);

        $this->actingAs($customer);

        $component = Livewire::test(MasterCalendar::class)
            ->assertSee('Assigned appointment')
            ->assertSee('Assigned wedding')
            ->assertDontSee('Private admin appointment')
            ->assertDontSee('Other wedding');

        $this->assertSame(
            ['Assigned appointment'],
            $component->instance()->getListItems()->pluck('title')->all(),
        );
    }

    public function test_admin_sees_calendar_items_for_all_projects(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $firstProject = Project::query()->create([
            'name' => 'First wedding',
            'last_name' => 'Client',
            'status' => 'confirmed',
        ]);
        $secondProject = Project::query()->create([
            'name' => 'Second wedding',
            'last_name' => 'Other',
            'status' => 'confirmed',
        ]);

        $firstProject->projectEvents()->create([
            'title' => 'First appointment',
            'starts_at' => '2026-07-22 00:00:00',
            'ends_at' => '2026-07-22 23:59:59',
            'is_all_day' => true,
        ]);
        $secondProject->projectEvents()->create([
            'title' => 'Second appointment',
            'starts_at' => '2026-07-23 00:00:00',
            'ends_at' => '2026-07-23 23:59:59',
            'is_all_day' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(MasterCalendar::class)
            ->assertSee('First appointment')
            ->assertSee('First wedding')
            ->assertSee('Second appointment')
            ->assertSee('Second wedding');
    }
}
