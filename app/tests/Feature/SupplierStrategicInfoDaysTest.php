<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectSupplier;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectRecap;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use App\Models\ProjectStrategicInfo;
use App\Models\ProjectStrategicInfoDetail;
use App\Models\Role;
use App\Models\StrategicInfo;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierStrategicInfoDaysTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    public function test_single_day_supplier_keeps_common_information_and_hides_daily_switch(): void
    {
        [$project, $proposal, $info] = $this->context(['2027-04-21']);
        $this->page($project, $proposal)
            ->mountAction('manageStrategicInfo', ['info' => $info->id])
            ->assertFormFieldIsHidden('separate_by_day', 'mountedActionSchema0')
            ->setActionData(['content' => '<p>Common menu</p>', 'status' => 'completed'])
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertFalse($info->refresh()->separate_by_day);
        $this->assertSame('<p>Common menu</p>', $info->commonDetail()->content);
        $this->assertCount(1, $info->details);
    }

    public function test_one_day_event_keeps_common_information_without_daily_switch(): void
    {
        [$project, $proposal, $info] = $this->context();
        $project->update(['event_end_date' => '2027-04-20']);
        $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id])
            ->assertFormFieldIsHidden('separate_by_day', 'mountedActionSchema0');
    }

    public function test_no_selected_days_means_all_days_and_switch_copies_legacy_content_only_to_first_day(): void
    {
        Storage::fake('public');
        [$project, $proposal, $info] = $this->context();
        $path = \Illuminate\Http\UploadedFile::fake()->image('existing-menu.jpg')->store('projects/strategic-infos', 'public');
        $completedAt = now()->subDays(5)->startOfSecond();
        $info->commonDetail()->update(['content' => '<p>Existing menu</p>', 'status' => 'completed', 'image_paths' => [$path], 'completed_at' => $completedAt]);
        $page = $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id])
            ->assertFormFieldIsVisible('separate_by_day', 'mountedActionSchema0')
            ->set('mountedActions.0.data.separate_by_day', true);
        $this->assertStringContainsString('Existing menu', json_encode($page->get('mountedActions.0.data.days.2027-04-20.content')));
        $this->assertStringNotContainsString('Existing menu', json_encode($page->get('mountedActions.0.data.days.2027-04-21.content')));
        // Opening and toggling do not convert the database until Save.
        $this->assertFalse($info->refresh()->separate_by_day);
        $page->callMountedAction()->assertHasNoActionErrors();
        $this->assertTrue($info->refresh()->separate_by_day);
        $this->assertNull($info->commonDetail());
        $this->assertSame(['2027-04-20', '2027-04-21', '2027-04-22'], $info->details->pluck('day_key')->all());
        $this->assertSame('<p>Existing menu</p>', $info->details->firstWhere('day_key', '2027-04-20')->content);
        $this->assertSame([$path], $info->details->firstWhere('day_key', '2027-04-20')->image_paths);
        $this->assertTrue($completedAt->equalTo($info->details->firstWhere('day_key', '2027-04-20')->completed_at));
        $this->assertEmpty($info->details->firstWhere('day_key', '2027-04-21')->image_paths);
        Storage::disk('public')->assertExists($path);
    }

    public function test_daily_edits_are_independent_and_recap_places_completed_content_in_its_day(): void
    {
        [$project, $proposal, $info] = $this->context(['2027-04-20', '2027-04-21']);
        $this->page($project, $proposal)->callAction('manageStrategicInfo', [
            'separate_by_day' => true,
            'days' => [
                '2027-04-20' => ['content' => '<p>Tuesday welcome menu</p>', 'image_paths' => [], 'status' => 'completed'],
                '2027-04-21' => ['content' => '<p>Wednesday wedding menu</p>', 'image_paths' => [], 'status' => 'completed'],
            ],
        ], arguments: ['info' => $info->id])->assertHasNoActionErrors();

        $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id])
            ->set('mountedActions.0.data.days.2027-04-21.content', '<p>Updated wedding menu</p>')
            ->callMountedAction()->assertHasNoActionErrors();
        $details = $info->refresh()->details->keyBy('day_key');
        $this->assertSame('<p>Tuesday welcome menu</p>', $details['2027-04-20']->content);
        $this->assertSame('<p>Updated wedding menu</p>', $details['2027-04-21']->content);
        $this->assertCount(2, $details);

        $recap = Livewire::test(ViewProjectRecap::class, ['record' => $project->id]);
        $items = $recap->instance()->getRecapStrategicInfos()->where('title', $info->title);
        $this->assertSame(['2027-04-20', '2027-04-21'], $items->pluck('day_key')->all());
        $recap->assertSeeInOrder(['Tuesday, April 20, 2027', 'Tuesday welcome menu', 'Wednesday, April 21, 2027', 'Updated wedding menu']);
        $payload = (new \ReflectionMethod($recap->instance(), 'getRecapStrategicInfoPdfItems'))->invoke($recap->instance());
        $this->assertSame(['2027-04-20', '2027-04-21'], $payload->where('title', $info->title)->pluck('day_key')->values()->all());
    }

    public function test_dietary_requirements_can_stay_common_while_menu_is_split(): void
    {
        [$project, $proposal, $menu] = $this->context();
        $dietDefinition = StrategicInfo::query()->firstOrCreate(['category_id' => $menu->category_id, 'title' => 'Dietary requirements']);
        $project->syncStrategicInfosFromTemplates();
        $diet = $project->strategicInfos()->where('strategic_info_id', $dietDefinition->id)->where('supplier_id', $proposal->supplier_id)->firstOrFail();
        $this->page($project, $proposal)->callAction('manageStrategicInfo', [
            'content' => '<p>Gluten free on all days</p>', 'status' => 'completed',
        ], arguments: ['info' => $diet->id])->assertHasNoActionErrors();
        $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $menu->id])
            ->set('mountedActions.0.data.separate_by_day', true)->callMountedAction()->assertHasNoActionErrors();
        $this->assertTrue($menu->refresh()->separate_by_day);
        $this->assertFalse($diet->refresh()->separate_by_day);
        $this->assertSame('common', $diet->details->sole()->day_key);
        Livewire::test(ViewProjectRecap::class, ['record' => $project->id])
            ->assertSee('Gluten free on all days')->assertSee('All supplier event days');
    }

    public function test_two_catering_suppliers_have_independent_information_and_cannot_edit_each_others_record(): void
    {
        [$project, $proposal, $info] = $this->context();
        $secondSupplier = Supplier::query()->create(['name' => 'Second daily caterer', 'category_id' => $info->category_id]);
        $secondProposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $proposal->category_budget_id, 'supplier_id' => $secondSupplier->id, 'proposal_status' => 'confirmed',
        ]);
        $project->syncStrategicInfosFromTemplates();
        $secondInfo = $project->strategicInfos()->where('strategic_info_id', $info->strategic_info_id)->where('supplier_id', $secondSupplier->id)->firstOrFail();
        $this->page($project, $proposal)->callAction('manageStrategicInfo', ['content' => '<p>First caterer only</p>'], arguments: ['info' => $info->id])
            ->assertHasNoActionErrors();
        $this->assertNotSame('<p>First caterer only</p>', $secondInfo->refresh()->commonDetail()->content);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->page($project, $secondProposal)->mountAction('manageStrategicInfo', ['info' => $info->id]);
    }

    public function test_returning_to_common_information_requires_explicit_choice_and_confirmation(): void
    {
        [$project, $proposal, $info] = $this->context(['2027-04-20', '2027-04-21']);
        $info->details()->delete();
        $info->update(['separate_by_day' => true]);
        $info->details()->createMany([
            ['day_key' => '2027-04-20', 'content' => '<p>First day</p>', 'status' => 'completed'],
            ['day_key' => '2027-04-21', 'content' => '<p>Second day</p>', 'status' => 'draft'],
        ]);
        $this->page($project, $proposal)->callAction('manageStrategicInfo', ['separate_by_day' => false], arguments: ['info' => $info->id])
            ->assertHasActionErrors();
        $this->assertTrue($info->refresh()->separate_by_day);
        $this->assertCount(2, $info->details);
        $this->page($project, $proposal)->callAction('manageStrategicInfo', [
            'separate_by_day' => false, 'common_source_day' => '2027-04-21', 'confirm_merge' => true,
        ], arguments: ['info' => $info->id])->assertHasNoActionErrors();
        $this->assertFalse($info->refresh()->separate_by_day);
        $this->assertSame('<p>Second day</p>', $info->commonDetail()->content);
        $this->assertCount(1, $info->details);
    }

    public function test_concurrent_changes_do_not_get_overwritten_by_an_old_open_modal(): void
    {
        [$project, $proposal, $info] = $this->context();
        $page = $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id]);
        $info->commonDetail()->update(['content' => '<p>Saved by another planner</p>']);
        $page->setActionData(['content' => '<p>Stale edit</p>'])->callMountedAction()->assertHasActionErrors();
        $this->assertSame('<p>Saved by another planner</p>', $info->refresh()->commonDetail()->content);
    }

    public function test_setup_defaults_never_overwrite_separated_daily_information(): void
    {
        [$project, $proposal, $info] = $this->context();
        $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id])
            ->set('mountedActions.0.data.separate_by_day', true)->callMountedAction()->assertHasNoActionErrors();
        $before = $info->refresh()->details->map->only(['day_key', 'content', 'status'])->all();
        $info->strategicInfo->update(['default_value' => '<p>New global template</p>']);
        $project->syncStrategicInfosFromTemplates();
        $this->assertSame($before, $info->refresh()->details->map->only(['day_key', 'content', 'status'])->all());
    }

    public function test_removing_one_shared_image_does_not_delete_the_file_used_by_another_supplier(): void
    {
        Storage::fake('public');
        [$project, $proposal, $info] = $this->context();
        $path = 'projects/strategic-infos/shared.jpg';
        Storage::disk('public')->put($path, 'shared file');
        $info->commonDetail()->update(['image_paths' => [$path]]);
        $other = $project->strategicInfos()->create(['category_id' => $info->category_id, 'title' => 'Other supplier image']);
        $other->details()->create(['day_key' => 'common', 'image_paths' => [$path], 'status' => 'draft']);
        $this->page($project, $proposal)->callAction('manageStrategicInfo', ['image_paths' => []], arguments: ['info' => $info->id])->assertHasNoActionErrors();
        Storage::disk('public')->assertExists($path);
        $this->assertSame([$path], $other->refresh()->commonDetail()->image_paths);
    }

    public function test_changed_supplier_dates_reject_stale_edits_and_retain_information_for_removed_days(): void
    {
        [$project, $proposal, $info] = $this->context(['2027-04-20', '2027-04-21']);
        $info->details()->delete();
        $info->update(['separate_by_day' => true]);
        $info->details()->createMany([
            ['day_key' => '2027-04-20', 'content' => '<p>Retain removed day</p>', 'status' => 'completed'],
            ['day_key' => '2027-04-21', 'content' => '<p>Current day information</p>', 'status' => 'completed'],
        ]);
        $page = $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id]);
        $proposal->update(['event_day_allocations' => [['date' => '2027-04-21', 'amount' => null], ['date' => '2027-04-22', 'amount' => null]]]);
        $page->callMountedAction()->assertHasActionErrors();
        $this->page($project, $proposal)->mountAction('manageStrategicInfo', ['info' => $info->id])
            ->set('mountedActions.0.data.days.2027-04-21.content', '<p>Current day updated</p>')
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('<p>Retain removed day</p>', $info->refresh()->details->firstWhere('day_key', '2027-04-20')->content);
        $this->assertSame('in_progress', $info->displayState());
        Livewire::test(ViewProjectRecap::class, ['record' => $project->id])
            ->assertSee('Current day updated')->assertDontSee('Retain removed day');
    }

    public function test_customers_can_preview_daily_information_but_cannot_manage_it(): void
    {
        [$project, $proposal, $info] = $this->context();
        $info->details()->delete();
        $info->update(['separate_by_day' => true]);
        $info->details()->create(['day_key' => '2027-04-20', 'content' => '<p>Customer daily menu</p>', 'status' => 'completed']);
        $role = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $role->id]);
        $project->users()->attach($customer);
        $this->actingAs($customer);
        $page = $this->page($project, $proposal)->assertActionHidden('manageStrategicInfo');
        $page->mountAction('previewStrategicInfo', ['info' => $info->id])->assertActionMounted('previewStrategicInfo');
        $html = view('filament.resources.project-resource.pages.partials.strategic-info-preview', ['info' => $info->refresh()])->render();
        $this->assertStringContainsString('Customer daily menu', $html);
        $this->assertStringContainsString('Tue 20 Apr 2027', $html);
    }

    public function test_recap_pdf_renders_daily_and_common_information(): void
    {
        [$project, $proposal, $info] = $this->context(['2027-04-20', '2027-04-21']);
        $info->details()->delete();
        $info->update(['separate_by_day' => true]);
        $info->details()->createMany([
            ['day_key' => '2027-04-20', 'content' => '<p>Welcome dinner menu for Tuesday</p>', 'status' => 'completed'],
            ['day_key' => '2027-04-21', 'content' => '<p>Wedding dinner menu for Wednesday</p>', 'status' => 'completed'],
        ]);
        $common = $project->strategicInfos()->create(['supplier_id' => $proposal->supplier_id, 'category_id' => $info->category_id, 'title' => 'Dietary requirements for all days']);
        $common->details()->create(['day_key' => 'common', 'content' => '<p>Gluten free meals on all supplier event days.</p>', 'status' => 'completed']);
        $page = Livewire::test(ViewProjectRecap::class, ['record' => $project->id]);
        $response = $page->instance()->exportTimelinePdf();
        ob_start();
        ($response->getCallback())();
        $pdf = ob_get_clean();
        $this->assertStringStartsWith('%PDF-', $pdf);
        // QA artifact only: the transaction rolls back all fixture records.
        Storage::disk('local')->put('private/strategic-recap-qa.pdf', $pdf);
    }

    protected function context(?array $dates = null): array
    {
        $category = Category::query()->firstOrCreate(['label' => 'Catering'], ['label_it' => 'Catering']);
        $definition = StrategicInfo::query()->firstOrCreate(['category_id' => $category->id, 'title' => 'Menu'], ['default_value' => '<p>Default menu</p>']);
        $project = Project::query()->create(['name' => 'Daily strategic wedding', 'last_name' => 'Client', 'event_start_date' => '2027-04-20', 'event_end_date' => '2027-04-22', 'event_date' => '2027-04-20']);
        $budget = CategoryBudget::query()->create(['project_id' => $project->id, 'category_id' => $category->id]);
        $supplier = Supplier::query()->create(['name' => 'Daily catering supplier', 'category_id' => $category->id]);
        $proposal = CategoryBudgetSupplier::query()->create([
            'category_budget_id' => $budget->id, 'supplier_id' => $supplier->id, 'proposal_status' => 'confirmed',
            'event_day_allocations' => $dates === null ? null : array_map(fn ($date): array => ['date' => $date, 'amount' => null], $dates),
        ]);
        $project->syncStrategicInfosFromTemplates();
        $info = $project->strategicInfos()->where('strategic_info_id', $definition->id)->where('supplier_id', $supplier->id)->firstOrFail();

        return [$project, $proposal, $info];
    }

    protected function page(Project $project, CategoryBudgetSupplier $proposal)
    {
        return Livewire::test(ManageProjectSupplier::class, ['record' => $project->id, 'proposal' => $proposal->id]);
    }
}
