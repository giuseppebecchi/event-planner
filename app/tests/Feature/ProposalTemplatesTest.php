<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadResource\Pages\ViewLeadProposal;
use App\Filament\Resources\ProposalTemplateResource\Pages\CreateProposalTemplate;
use App\Filament\Resources\ProposalTemplateResource\Pages\ViewProposalTemplate;
use App\Http\Controllers\VisualProposalPdfController;
use App\Models\Lead;
use App\Models\ProposalTemplate;
use App\Models\Role;
use App\Models\User;
use App\Support\ProposalConfiguration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProposalTemplatesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    protected function template(): ProposalTemplate
    {
        return ProposalTemplate::create(['name' => 'QA template', 'slug' => 'qa-template', 'is_active' => true,
            'configuration' => [...ProposalConfiguration::defaults('lake_como'),
                'planning_service' => '<ul><li>Template planning service</li></ul>',
                'conditions' => '<h2>Conditions</h2><ul><li>Template deposit</li></ul><h2>Offer validity</h2><p>Valid until {{ proposal_valid_until }}</p>']]);
    }

    protected function lead(array $attributes = []): Lead
    {
        return Lead::create(['first_name' => 'QA', 'last_name' => 'Proposal', 'couple_name' => 'QA couple',
            'wedding_date' => '2028-05-18', ...$attributes]);
    }

    public function test_import_is_idempotent_and_does_not_convert_or_rewrite_existing_leads(): void
    {
        $lead = $this->lead(['proposal_sent_at' => now(), 'proposal_content' => '<p>Legacy sent conditions</p>']);
        $before = $lead->fresh()->getAttributes();
        $this->artisan('proposal-templates:import-defaults')->assertSuccessful();
        $tuscany = ProposalTemplate::where('slug', 'tuscany')->firstOrFail();
        $tuscany->update(['name' => 'Edited Tuscany']);
        $this->artisan('proposal-templates:import-defaults')->assertSuccessful();
        $this->assertSame('Edited Tuscany', $tuscany->fresh()->name);
        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    public function test_conversion_keeps_existing_draft_text_images_and_budget(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('leads/proposal-images/custom.png', UploadedFile::fake()->image('photo.png')->get());
        $lead = $this->lead(['proposal_content' => '<p>Agreed deposit</p>',
            'proposal_wedding_planning_service' => '<p>Custom service</p>',
            'proposal_images_json_config' => ['cover_bride' => 'leads/proposal-images/custom.png', 'template' => 'lake_como'],
            'budget_wedding_planner' => [['label' => 'Agreed fee', 'amount' => 4200]]]);
        Livewire::test(ViewLeadProposal::class, ['record' => $lead->id])->callAction('createVisualProposal', ['template_id' => $this->template()->id])->assertHasNoActionErrors();
        $lead->refresh();
        $this->assertSame('<p>Agreed deposit</p>', $lead->proposal_content);
        $this->assertSame('<p>Custom service</p>', $lead->proposal_wedding_planning_service);
        $this->assertSame('leads/proposal-images/custom.png', $lead->proposal_layout['images']['cover_bride']);
        $this->assertSame(4200, $lead->budget_wedding_planner[0]['amount']);
    }

    public function test_sent_legacy_proposal_cannot_be_converted_even_by_calling_the_backend(): void
    {
        $lead = $this->lead(['proposal_sent_at' => now()]);
        Livewire::test(ViewLeadProposal::class, ['record' => $lead->id])->assertActionHidden('createVisualProposal');
        try {
            ProposalConfiguration::apply($lead, $this->template(), true);
            $this->fail('Sent legacy proposal was converted');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertNull($lead->fresh()->proposal_layout);
    }

    public function test_template_changes_do_not_update_existing_proposal_content_or_images(): void
    {
        $template = $this->template();
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $template, false);
        $before = ProposalConfiguration::forLead($lead);
        $config = $template->configuration;
        $config['cover_title'] = '<p>New branding</p>';
        $config['planning_service'] = '<p>New service</p>';
        $config['images']['cover_bride'] = $config['images']['cover_venue'];
        $template->update(['configuration' => $config]);
        $this->assertEquals($before, ProposalConfiguration::forLead($lead->fresh()));
    }

    public function test_template_text_can_be_edited_from_the_preview_popup(): void
    {
        $template = $this->template();
        $before = $template->configuration;
        Livewire::test(ViewProposalTemplate::class, ['record' => $template->id])
            ->callAction('editProposalElement', ['content' => '<p>Elopement [REGION]</p>'], arguments: ['key' => 'proposal_title'])
            ->assertHasNoActionErrors();
        $after = $template->fresh()->configuration;
        $this->assertSame('<p>Elopement [REGION]</p>', $after['proposal_title']);
        unset($before['proposal_title'], $after['proposal_title']);
        $this->assertEquals($before, $after);
    }

    public function test_lead_editor_updates_the_existing_service_field_only_for_that_lead(): void
    {
        $template = $this->template();
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $template, false);
        Livewire::test(ViewLeadProposal::class, ['record' => $lead->id])
            ->callAction('editProposalElement', ['content' => '<p>Lead service only</p>'], arguments: ['key' => 'planning_service'])
            ->assertHasNoActionErrors();
        $this->assertSame('<p>Lead service only</p>', $lead->fresh()->proposal_wedding_planning_service);
        $this->assertSame('<ul><li>Template planning service</li></ul>', $template->fresh()->configuration['planning_service']);
        $this->assertArrayNotHasKey('planning_service', $lead->fresh()->proposal_layout);
    }

    public function test_lead_editor_keeps_a_stable_root_after_saving_and_can_open_another_element(): void
    {
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $this->template(), false);
        $editor = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);

        $assertRoot = function () use ($editor): void {
            $this->assertMatchesRegularExpression('/^<div\\s[^>]*wire:id="[^"]+"[^>]*>/s', trim($editor->html()));
        };
        $assertRoot();
        $editor->callAction('editProposalElement', ['content' => '<p>Updated title</p>'], arguments: ['key' => 'cover_title'])
            ->assertHasNoActionErrors();
        $assertRoot();
        $editor->mountAction('editProposalElement', arguments: ['key' => 'cover_subtitle'])
            ->assertActionMounted('editProposalElement');
    }

    public function test_visual_sidebar_actions_remain_available_without_header_buttons(): void
    {
        $lead = $this->lead();
        $legacy = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);
        $this->assertSame([], $legacy->instance()->getCachedHeaderActions());
        ProposalConfiguration::apply($lead, $this->template(), false);
        $editor = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);
        $this->assertSame([], $editor->instance()->getCachedHeaderActions());
        $editor->assertSeeInOrder(['<h3>Proposal</h3>', '<h3>Sending status</h3>', '<h3>Client response</h3>'], escape: false);
        foreach (['generatePhase', 'markProposalSent', 'saveClientResponse', 'addProposalNote'] as $action) {
            $editor->mountAction($action)->assertActionMounted($action)->unmountAction();
        }
    }

    public function test_service_columns_are_independently_editable_in_templates_and_proposals(): void
    {
        $template = $this->template();
        $left = $template->configuration['planning_service'];
        Livewire::test(ViewProposalTemplate::class, ['record' => $template->id])
            ->callAction('editProposalElement', ['content' => '<p>Template right only</p>'], arguments: ['key' => 'planning_service_right'])
            ->assertHasNoActionErrors();
        $this->assertSame($left, $template->fresh()->configuration['planning_service']);
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $template->fresh(), false);
        $left = $lead->fresh()->proposal_wedding_planning_service;
        $editor = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);
        $editor->callAction('editProposalElement', ['content' => '<p>Lead right only</p>'], arguments: ['key' => 'planning_service_right'])
            ->assertHasNoActionErrors();
        $this->assertSame($left, $lead->fresh()->proposal_wedding_planning_service);
        $editor->callAction('editProposalElement', ['content' => '<p>Left one</p><p>Left two</p><p>Left three</p>'], arguments: ['key' => 'planning_service'])
            ->assertHasNoActionErrors();
        $this->assertSame('<p>Lead right only</p>', $lead->fresh()->proposal_layout['planning_service_right']);
        $this->assertSame('<p>Template right only</p>', $template->fresh()->configuration['planning_service_right']);
        $html = $this->get(route('admin.leads.proposal.preview', $lead))->assertOk()->getContent();
        $this->assertStringContainsString('<p>Left one</p><p>Left two</p><p>Left three</p>', $html);
        $this->assertStringContainsString('<p>Lead right only</p>', $html);
        $this->assertSame(1, substr_count($html, 'data-edit="planning_service"'));
        $this->assertSame(1, substr_count($html, 'data-edit="planning_service_right"'));
    }

    public function test_service_split_preserves_the_visible_midpoint_and_excludes_legacy_leads(): void
    {
        Storage::fake('local');
        $original = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';
        $template = $this->template();
        $config = $template->configuration;
        unset($config['planning_service_right']);
        $config['planning_service'] = $original;
        $template->update(['configuration' => $config]);
        $visual = $this->lead(['proposal_layout' => ['source_variant' => 'tuscany'], 'proposal_wedding_planning_service' => $original]);
        $legacy = $this->lead(['proposal_wedding_planning_service' => $original, 'proposal_sent_at' => now()]);
        $this->artisan('proposal-templates:split-service --apply')->assertSuccessful();
        $this->assertSame('<p>One</p><p>Two</p>', $visual->fresh()->proposal_wedding_planning_service);
        $this->assertSame('<p>Three</p>', $visual->fresh()->proposal_layout['planning_service_right']);
        $this->assertSame('<p>One</p><p>Two</p>', $template->fresh()->configuration['planning_service']);
        $this->assertSame('<p>Three</p>', $template->fresh()->configuration['planning_service_right']);
        $this->assertSame($original, $legacy->fresh()->proposal_wedding_planning_service);
        $this->assertNotEmpty(Storage::disk('local')->allFiles('proposal-service-backups'));
        $this->artisan('proposal-templates:split-service --apply')->assertSuccessful();
        $this->assertSame('<p>One</p><p>Two</p>', $visual->fresh()->proposal_wedding_planning_service);
    }

    public function test_photo_replacement_retains_files_used_by_an_existing_proposal(): void
    {
        Storage::fake('public');
        $template = $this->template();
        $old = 'proposal-templates/images/old.png';
        Storage::disk('public')->put($old, UploadedFile::fake()->image('old.png')->get());
        $config = $template->configuration;
        $config['images']['cover_bride'] = $old;
        $template->update(['configuration' => $config]);
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $template, false);
        Livewire::test(ViewProposalTemplate::class, ['record' => $template->id])
            ->callAction('editProposalElement', ['image' => UploadedFile::fake()->image('new.png')], arguments: ['key' => 'images.cover_bride'])
            ->assertHasNoActionErrors();
        $this->assertNotSame($old, $template->fresh()->configuration['images']['cover_bride']);
        $this->assertSame($old, $lead->fresh()->proposal_layout['images']['cover_bride']);
        Storage::disk('public')->assertExists($old);
    }

    public function test_date_variables_use_the_event_date_in_english(): void
    {
        $lead = $this->lead(['wedding_period' => 'September 2027']);
        $this->assertSame('MAY 2028', ProposalConfiguration::replaceVariables('[MONTH] [YYYY]', $lead));
        $lead->wedding_date = null;
        $this->assertSame('SEPTEMBER 2027', ProposalConfiguration::replaceVariables('[MONTH] [YYYY]', $lead));
    }

    public function test_conditions_render_as_one_rich_text_block_with_all_edited_content(): void
    {
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $this->template(), false);
        $text = '<h2>Custom terms</h2><p>First <strong>deposit</strong></p><p>Valid for 20 days</p>';
        Livewire::test(ViewLeadProposal::class, ['record' => $lead->id])
            ->callAction('editProposalElement', ['content' => $text], arguments: ['key' => 'conditions'])
            ->assertHasNoActionErrors();
        $response = $this->get(route('admin.leads.proposal.preview', $lead));
        $response->assertOk()->assertSee($text, escape: false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-edit="conditions"'));
        $this->assertSame($text, $lead->fresh()->proposal_content);
    }

    public function test_visual_rendering_preserves_empty_editor_paragraphs_without_changing_saved_text(): void
    {
        $lead = $this->lead();
        $config = $this->template()->configuration;
        $config['conditions'] = '<p>First paragraph</p><p></p><p><br></p><p><strong>Offer validity</strong></p>';
        $config['cover_subtitle'] = '<p>Subtitle</p><p></p><p>Second line</p>';
        $controller = app(VisualProposalPdfController::class);
        foreach ([false, true] as $preview) {
            $data = $controller->viewData($lead, $config, $preview);
            $this->assertSame('<p>First paragraph</p><p>&nbsp;</p><p>&nbsp;</p><p><strong>Offer validity</strong></p>', $data['rich']['conditions']);
            $this->assertSame('<p>Subtitle</p><p>&nbsp;</p><p>Second line</p>', $data['rich']['cover_subtitle']);
        }
        $this->assertSame('<p>First paragraph</p><p></p><p><br></p><p><strong>Offer validity</strong></p>', $config['conditions']);
    }

    public function test_conditions_cleanup_backs_up_only_visual_data_and_is_not_repeated(): void
    {
        Storage::fake('local');
        $original = '<h1>Proposal</h1><h2>Conditions</h2><p>To proceed with the confirmation:</p>'
            .'<ul><li>Agreed deposit</li><li>Balance</li></ul><h2>Offer validity</h2><p>Valid for 30 days</p>';
        $template = $this->template();
        $config = $template->configuration;
        unset($config['conditions_format']);
        $config['conditions'] = $original;
        $template->update(['configuration' => $config]);
        $visual = $this->lead(['proposal_layout' => ['source_variant' => 'tuscany'], 'proposal_content' => $original]);
        $legacy = $this->lead(['proposal_content' => $original, 'proposal_sent_at' => now()]);

        $this->artisan('proposal-templates:normalize-conditions')->assertSuccessful();
        $this->assertSame($original, $visual->fresh()->proposal_content);
        $this->artisan('proposal-templates:normalize-conditions --apply')->assertSuccessful();
        $expected = '<p>Agreed deposit</p><p>Balance</p><p>Valid for 30 days</p>';
        $this->assertSame($expected, $template->fresh()->configuration['conditions']);
        $this->assertSame($expected, $visual->fresh()->proposal_content);
        $this->assertSame($original, $legacy->fresh()->proposal_content);
        $backup = Storage::disk('local')->allFiles('proposal-conditions-backups');
        $this->assertCount(1, $backup);
        $this->assertStringContainsString('To proceed with the confirmation:', Storage::disk('local')->get($backup[0]));

        $visual->update(['proposal_content' => '<h2>New custom heading</h2><p>New terms</p>']);
        $this->artisan('proposal-templates:normalize-conditions --apply')->assertSuccessful();
        $this->assertSame('<h2>New custom heading</h2><p>New terms</p>', $visual->fresh()->proposal_content);
    }

    public function test_rendering_keeps_budget_priority_and_existing_extra_service_rules(): void
    {
        $lead = $this->lead(['budget_wedding_planner' => [['label' => 'Agreed fee', 'amount' => 4200]],
            'budget_wedding_planner_extra_services' => [['label' => 'Extra', 'amount' => 0]],
            'budget_wedding_planner_special_packages' => [['label' => 'Special package', 'amount' => 700]]]);
        ProposalConfiguration::apply($lead, $this->template(), false);
        $result = app(VisualProposalPdfController::class)->viewData($lead, ProposalConfiguration::forLead($lead));
        $this->assertSame('4.200 euros', $result['data']['main_fee']);
        $this->assertCount(8, $result['data']['extra_rows']);
        $this->assertSame(['Template deposit'], $result['data']['confirmation_rows']);
        $this->assertStringContainsString('MAY 2028', $result['rich']['proposal_subtitle']);
        $this->assertCount(15, $result['images']);
    }

    public function test_legacy_download_keeps_the_original_pdf_view(): void
    {
        $lead = $this->lead(['proposal_sent_at' => now(), 'proposal_wedding_planning_service' => '<p>Legacy service</p>']);
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with('pdf.lead-proposal', Mockery::type('array'))->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('legacy-pdf'));
        $this->get(route('admin.leads.proposal.pdf', $lead))->assertOk()->assertSee('legacy-pdf');
        $this->assertNull($lead->fresh()->proposal_layout);
    }

    public function test_empty_lead_starts_with_visual_creation_without_writing_until_confirmed(): void
    {
        $template = $this->template();
        $lead = $this->lead(['budget_wedding_planner' => [['label' => 'Agreed fee', 'amount' => 3800]]]);
        $before = $lead->fresh()->getAttributes();
        $editor = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);
        $this->assertTrue($editor->instance()->usesVisualProposalFlow());
        $editor->assertSee('Create visual proposal')->assertSee('Choose template')
            ->assertDontSee('Configure images')->assertDontSee('Switch to visual proposal editor')
            ->assertDontSee('Export PDF')->assertDontSee('Download PDF');
        $this->assertSame($before, $lead->fresh()->getAttributes());
        $editor->mountAction('generatePhase')->assertActionMounted('generatePhase')->unmountAction();
        $this->assertSame($before, $lead->fresh()->getAttributes());
        $editor->callAction('generatePhase', ['template_id' => $template->id])->assertHasNoActionErrors()
            ->assertSee('Download PDF')->assertSee('Regenerate / change template')->assertDontSee('Create your proposal');
        $this->assertNotEmpty($lead->fresh()->proposal_layout);
        $this->assertEquals($before['budget_wedding_planner'], $lead->fresh()->getAttributes()['budget_wedding_planner']);
    }

    public function test_any_existing_proposal_data_keeps_the_legacy_flow(): void
    {
        foreach ([
            ['proposal_content' => '<p>Existing draft</p>'],
            ['proposal_content' => '<p></p>'],
            ['proposal_wedding_planning_service' => '<p>Custom service</p>'],
            ['proposal_images_json_config' => ['template' => 'lake_como']],
            ['proposal_sent_at' => now()],
            ['proposal_response_at' => now()],
            ['proposal_response_status' => 'awaiting'],
            ['proposal_notes_log' => [['note' => 'Existing note', 'created_at' => now()->toDateTimeString()]]],
        ] as $attributes) {
            $lead = $this->lead($attributes);
            $before = $lead->fresh()->getAttributes();
            $editor = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);
            $this->assertFalse($editor->instance()->usesVisualProposalFlow());
            $editor->assertSee('Export PDF')->assertSee('Configure images')->assertDontSee('Create visual proposal');
            $this->assertSame($before, $lead->fresh()->getAttributes());
        }
    }

    public function test_legacy_sidebar_is_read_only_and_preserves_the_pdf_inputs_until_explicit_conversion(): void
    {
        $lead = $this->lead(['proposal_sent_at' => now(), 'proposal_content' => '<h2>Agreed terms</h2><p>Custom deposit</p>',
            'proposal_wedding_planning_service' => '<ul><li>Legacy first</li><li>Legacy second</li></ul>',
            'proposal_images_json_config' => ['template' => 'lake_como'],
            'budget_wedding_planner' => [['label' => 'Agreed fee', 'amount' => 5555]],
            'proposal_notes_log' => [['note' => 'Existing variation', 'created_at' => now()->toDateTimeString()]]]);
        $before = $lead->fresh()->getAttributes();
        $pdfInputs = [];
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->twice()->with('pdf.lead-proposal', Mockery::on(function (array $input) use (&$pdfInputs): bool {
            $pdfInputs[] = array_intersect_key($input, array_flip(['data', 'images', 'fonts']));

            return true;
        }))->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->twice()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->twice()->andReturn(response('unchanged-legacy-pdf'));
        $this->get(route('admin.leads.proposal.pdf', $lead))->assertOk();
        $editor = Livewire::test(ViewLeadProposal::class, ['record' => $lead->id]);
        $this->assertSame([], $editor->instance()->getCachedHeaderActions());
        $editor->assertSee('Export PDF')->assertSee('Existing variation')->assertDontSee('Switch to visual proposal editor')
            ->assertSeeInOrder(['visual-proposal-aside', 'Export PDF', 'Mark proposal as sent', 'Save client response', 'Add note / variation', 'legacy-proposal-content'], escape: false);
        foreach (['configureProposal', 'configureProposalImages', 'markProposalSent', 'saveClientResponse', 'addProposalNote'] as $action) {
            $editor->mountAction($action)->assertActionMounted($action)->unmountAction();
        }
        $this->get(route('admin.leads.proposal.pdf', $lead))->assertOk();
        $this->assertEquals($pdfInputs[0], $pdfInputs[1]);
        $this->assertSame($before, $lead->fresh()->getAttributes());

        $draft = $this->lead(['proposal_content' => '<p>Legacy draft</p>']);
        Livewire::test(ViewLeadProposal::class, ['record' => $draft->id])
            ->assertSeeInOrder(['legacy-proposal-toolbar', 'Generate', 'Configure proposal', 'Configure images', 'Switch to visual proposal editor', 'legacy-proposal-content'], escape: false)
            ->assertDontSee('Legacy proposal')->mountAction('createVisualProposal')->assertActionMounted('createVisualProposal');
        $this->assertNull($draft->fresh()->proposal_layout);
    }

    public function test_preview_routes_are_not_accessible_to_customers(): void
    {
        $template = $this->template();
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $template, false);
        $role = Role::firstOrCreate(['name' => Role::CUSTOMER]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->get(route('admin.proposal-templates.preview', $template))->assertForbidden();
        $this->get(route('admin.proposal-templates.pdf', $template))->assertForbidden();
        $this->get(route('admin.leads.proposal.preview', $lead))->assertForbidden();
        $this->get('/admin/proposal-templates')->assertRedirect();
    }

    public function test_staff_preview_has_four_pages_and_edit_controls_without_mutating_the_lead(): void
    {
        $template = $this->template();
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $template, false);
        $before = $lead->fresh()->getAttributes();
        $response = $this->get(route('admin.leads.proposal.preview', $lead))->assertOk();
        $this->assertSame(4, substr_count($response->getContent(), '<section class="page">'));
        $response->assertSee('proposal-edit')->assertSee('MAY 2028');
        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    public function test_creating_a_variant_copies_the_selected_template(): void
    {
        $base = $this->template();
        Livewire::test(CreateProposalTemplate::class)->fillForm([
            'name' => 'Elopement', 'slug' => 'qa-elopement', 'is_active' => true, 'base_template' => $base->id,
        ])->call('create')->assertHasNoFormErrors();
        $this->assertEquals($base->configuration, ProposalTemplate::where('slug', 'qa-elopement')->firstOrFail()->configuration);
    }

    public function test_visual_download_uses_the_saved_configuration_and_the_new_pdf_view(): void
    {
        $lead = $this->lead();
        ProposalConfiguration::apply($lead, $this->template(), false);
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with('pdf.visual-proposal', Mockery::on(
            fn (array $data): bool => $data['preview'] === false && str_contains($data['rich']['proposal_subtitle'], 'MAY 2028')
        ))->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('visual-pdf'));
        $this->get(route('admin.leads.proposal.pdf', $lead))->assertOk()->assertSee('visual-pdf');
    }

    public function test_inactive_templates_remain_available_to_existing_proposals_but_cannot_be_applied(): void
    {
        $template = $this->template();
        $existing = $this->lead();
        ProposalConfiguration::apply($existing, $template, false);
        $template->update(['is_active' => false]);
        $this->get(route('admin.leads.proposal.preview', $existing))->assertOk();
        $newLead = $this->lead();
        try {
            ProposalConfiguration::apply($newLead, $template, false);
            $this->fail('Inactive template was applied');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertNull($newLead->fresh()->proposal_layout);
    }

    public function test_image_resolution_rejects_other_files_and_directory_traversal(): void
    {
        $this->assertNull(ProposalConfiguration::imagePath('/etc/passwd'));
        $this->assertNull(ProposalConfiguration::imagePath('images/proposal/../../../.env'));
        $this->assertNull(ProposalConfiguration::imagePath('projects/documents/private.pdf'));
        $this->assertNull(ProposalConfiguration::imagePath('https://example.com/image.png'));
    }
}
