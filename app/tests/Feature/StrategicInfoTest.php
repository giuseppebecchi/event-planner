<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectSupplier;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectRecap;
use App\Filament\Resources\StrategicInfoResource;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use App\Models\ProjectStrategicInfo;
use App\Models\Role;
use App\Models\StrategicInfo;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class StrategicInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_project_receives_strategic_info_templates_and_pristine_instances_follow_setup_changes(): void
    {
        $firstCategory = $this->createCategory('Strategic first');
        $secondCategory = $this->createCategory('Strategic second');
        $definition = StrategicInfo::query()->create([
            'category_id' => $firstCategory->id,
            'title' => 'Original title',
            'default_value' => '<p><strong>Original field:</strong></p>',
            'order' => 1,
        ]);
        $project = $this->createProject('Strategic sync wedding');
        $instance = $project->strategicInfos()->where('strategic_info_id', $definition->id)->firstOrFail();

        $this->assertSame('<p><strong>Original field:</strong></p>', $instance->content);

        $definition->update([
            'category_id' => $secondCategory->id,
            'title' => 'Updated title',
            'default_value' => '<p><strong>Updated field:</strong></p>',
        ]);

        $instance->refresh();
        $this->assertSame('Updated title', $instance->title);
        $this->assertSame($secondCategory->id, $instance->category_id);
        $this->assertSame('<p><strong>Updated field:</strong></p>', $instance->content);

        $instance->update(['content' => '<p>Chosen details</p>']);
        $definition->update([
            'category_id' => $firstCategory->id,
            'title' => 'Later setup title',
        ]);

        $instance->refresh();
        $this->assertSame('Updated title', $instance->title);
        $this->assertSame($secondCategory->id, $instance->category_id);
        $this->assertSame('<p>Chosen details</p>', $instance->content);
    }

    public function test_seeded_strategic_infos_have_english_default_values(): void
    {
        $this->assertStringContainsString('Chairs:', StrategicInfo::query()->where('title', 'Mise en place')->value('default_value'));
        $this->assertStringContainsString('Aperitif', StrategicInfo::query()->where('title', 'Menu')->value('default_value'));
        $this->assertStringContainsString('Ceremony entrance:', StrategicInfo::query()->where('title', 'Playlist')->value('default_value'));
    }

    public function test_strategic_info_display_state_uses_content_and_explicit_status(): void
    {
        $info = new ProjectStrategicInfo(['status' => ProjectStrategicInfo::STATUS_DRAFT]);
        $this->assertSame('empty', $info->displayState());

        $info->content = '<p>Work in progress</p>';
        $this->assertSame('in_progress', $info->displayState());

        $info->status = ProjectStrategicInfo::STATUS_NOT_REQUIRED;
        $this->assertSame('not_required', $info->displayState());

        $info->status = ProjectStrategicInfo::STATUS_COMPLETED;
        $this->assertSame('completed', $info->displayState());
    }

    public function test_only_admins_manage_strategic_infos_while_customers_can_view_them(): void
    {
        $category = $this->createCategory('Strategic catering');
        $definition = StrategicInfo::query()->create([
            'category_id' => $category->id,
            'title' => 'Menu',
            'order' => 1,
        ]);
        $project = $this->createProject('Strategic supplier wedding');
        $budget = CategoryBudget::query()->create([
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'Strategic caterer',
            'category_id' => $category->id,
        ]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id,
            'supplier_id' => $supplier->id,
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
        ]);
        $instance = $project->strategicInfos()->where('strategic_info_id', $definition->id)->firstOrFail();
        $admin = $this->createUser(Role::ADMIN);

        $this->actingAs($admin);

        Livewire::test(ManageProjectSupplier::class, [
            'record' => $project->id,
            'proposal' => $proposal->id,
        ])
            ->assertSee('Menu')
            ->assertSee('To fill')
            ->callAction('manageStrategicInfo', [
                'content' => '<p>Vegetarian menu</p>',
                'image_paths' => [],
                'status' => ProjectStrategicInfo::STATUS_COMPLETED,
            ], arguments: ['info' => $instance->id])
            ->assertHasNoActionErrors();

        $instance->refresh();
        $this->assertSame(ProjectStrategicInfo::STATUS_COMPLETED, $instance->status);
        $this->assertSame('<p>Vegetarian menu</p>', $instance->content);
        $this->assertNotNull($instance->completed_at);

        $customer = $this->createUser(Role::CUSTOMER);
        $project->users()->attach($customer);
        $this->actingAs($customer);

        $component = Livewire::test(ManageProjectSupplier::class, [
            'record' => $project->id,
            'proposal' => $proposal->id,
        ]);

        $this->assertFalse($component->instance()->canManageStrategicInfos());
        $component->assertSee('Menu')->assertSee('View');
    }

    public function test_recap_contains_only_completed_strategic_infos(): void
    {
        $category = $this->createCategory('Strategic music');
        $completedDefinition = StrategicInfo::query()->create([
            'category_id' => $category->id,
            'title' => 'Playlist',
            'order' => 1,
        ]);
        $draftDefinition = StrategicInfo::query()->create([
            'category_id' => $category->id,
            'title' => 'Draft music note',
            'order' => 2,
        ]);
        $project = $this->createProject('Strategic recap wedding');

        $project->strategicInfos()->where('strategic_info_id', $completedDefinition->id)->update([
            'content' => '<p>First dance song</p>',
            'status' => ProjectStrategicInfo::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        $project->strategicInfos()->where('strategic_info_id', $draftDefinition->id)->update([
            'content' => '<p>Still choosing</p>',
            'status' => ProjectStrategicInfo::STATUS_DRAFT,
        ]);
        $this->actingAs($this->createUser(Role::ADMIN));

        $infos = Livewire::test(ViewProjectRecap::class, [
            'record' => $project->id,
        ])->instance()->getRecapStrategicInfos();

        $this->assertCount(1, $infos);
        $this->assertSame('Playlist', $infos->first()->title);
    }

    public function test_only_admin_roles_can_open_strategic_info_setup(): void
    {
        $this->actingAs($this->createUser(Role::ADMIN));
        $this->assertTrue(StrategicInfoResource::canViewAny());

        $this->actingAs($this->createUser(Role::COLLABORATOR));
        $this->assertFalse(StrategicInfoResource::canViewAny());

        $this->actingAs($this->createUser(Role::CUSTOMER));
        $this->assertFalse(StrategicInfoResource::canViewAny());
    }

    protected function createCategory(string $label): Category
    {
        return Category::query()->create([
            'label' => $label,
            'label_it' => $label,
            'order' => 100,
        ]);
    }

    protected function createProject(string $name): Project
    {
        return Project::query()->create([
            'name' => $name,
            'last_name' => 'Client',
        ]);
    }

    protected function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
