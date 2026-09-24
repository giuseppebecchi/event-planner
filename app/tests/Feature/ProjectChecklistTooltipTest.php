<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectChecklist;
use App\Models\Checklist;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectChecklistTooltipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_checklist_exposes_full_truncated_text_on_hover(): void
    {
        $project = Project::query()->create([
            'name' => 'Tooltip wedding',
            'last_name' => 'Client',
        ]);
        $checklist = Checklist::query()->create([
            'title' => 'Tooltip checklist',
            'options' => [],
        ]);
        $longTitle = 'Define stationery needs and invitation timelines with the selected supplier';
        $longDetails = 'Confirm paper, envelopes, printing finishes and the complete delivery schedule.';

        $project->projectChecklistOptions()->create([
            'checkbox_id' => $checklist->id,
            'order' => 1,
            'title' => $longTitle,
            'details' => $longDetails,
            'default' => false,
            'to_be_filled' => true,
            'anticipation' => '8 months',
            'assigned_to' => 'client',
            'enabled' => true,
            'completed' => false,
        ]);

        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        $project->users()->attach($customer);
        $this->actingAs($customer);

        Livewire::test(ViewProjectChecklist::class, ['record' => $project->id])
            ->assertSeeHtml('data-checklist-tooltip="'.$longTitle.'"')
            ->assertSeeHtml('data-checklist-tooltip="'.$longDetails.'"')
            ->assertSee('Fill info')
            ->assertDontSee('Insert response');
    }

    public function test_admin_checklist_exposes_full_truncated_text_on_hover(): void
    {
        $project = Project::query()->create([
            'name' => 'Admin tooltip wedding',
            'last_name' => 'Client',
        ]);
        $checklist = Checklist::query()->create([
            'title' => 'Admin tooltip checklist',
            'options' => [],
        ]);
        $longTitle = 'Coordinate every stationery delivery milestone with the selected supplier';

        $project->projectChecklistOptions()->create([
            'checkbox_id' => $checklist->id,
            'order' => 1,
            'title' => $longTitle,
            'default' => false,
            'assigned_to' => 'admin',
            'enabled' => true,
            'completed' => false,
        ]);

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Livewire::test(ViewProjectChecklist::class, ['record' => $project->id])
            ->assertSeeHtml('data-checklist-tooltip="'.$longTitle.'"');
    }
}
