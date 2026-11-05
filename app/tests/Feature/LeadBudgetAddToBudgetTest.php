<?php

namespace Tests\Feature;

use App\Http\Controllers\LeadBudgetPdfController;
use App\Http\Controllers\LeadProposalPdfController;
use App\Models\Lead;
use App\Models\Supplier;
use App\Support\LeadContractPdfRenderer;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeadBudgetAddToBudgetTest extends TestCase
{
    public function test_client_budget_pdf_includes_only_selected_extra_services_and_special_packages(): void
    {
        $lead = new Lead([
            'budget_wedding_planner' => [
                ['label' => 'Planning fee', 'amount' => 5000],
            ],
            'budget_wedding_planner_extra_services' => [
                ['label' => 'Selected extra', 'amount' => 300, 'add_to_budget' => true],
                ['label' => 'Proposal-only extra', 'amount' => 200, 'add_to_budget' => false],
            ],
            'budget_wedding_planner_special_packages' => [
                ['label' => 'Selected package', 'amount' => 700, 'add_to_budget' => true],
                ['label' => 'Proposal-only package', 'amount' => 400, 'add_to_budget' => false],
            ],
        ]);

        $controller = new LeadBudgetPdfController();
        $sections = $this->callProtected($controller, 'sections', $lead);
        $grandTotal = $this->callProtected($controller, 'grandTotal', $lead);

        $this->assertSame(['Selected extra'], collect($sections[2]['rows'])->pluck('label')->all());
        $this->assertSame(['Selected package'], collect($sections[3]['rows'])->pluck('label')->all());
        $this->assertSame(6000.0, $grandTotal);
    }

    public function test_contract_total_fee_includes_selected_extra_services_and_special_packages(): void
    {
        $lead = new Lead([
            'budget_wedding_planner' => [
                ['label' => 'Planning fee', 'amount' => 5000],
            ],
            'budget_wedding_planner_extra_services' => [
                ['label' => 'Selected extra', 'amount' => 300, 'add_to_budget' => true],
                ['label' => 'Proposal-only extra', 'amount' => 200, 'add_to_budget' => false],
            ],
            'budget_wedding_planner_special_packages' => [
                ['label' => 'Selected package', 'amount' => 700, 'add_to_budget' => true],
                ['label' => 'Proposal-only package', 'amount' => 400, 'add_to_budget' => false],
            ],
        ]);

        $content = app(LeadContractPdfRenderer::class)->replacePlaceholders('{{ contract_total_fee }}', $lead);

        $this->assertSame('6.000 euros', $content);
    }

    public function test_proposal_keeps_all_positive_extra_services_and_special_packages(): void
    {
        $lead = new Lead([
            'wedding_period' => 'September 2027',
            'desired_region' => 'Tuscany',
            'proposal_wedding_planning_service' => '<p>Planning support</p>',
            'budget_wedding_planner' => [
                ['label' => 'Planning fee', 'amount' => 5000],
            ],
            'budget_wedding_planner_extra_services' => [
                ['label' => 'Selected extra', 'amount' => 300, 'add_to_budget' => true],
                ['label' => 'Proposal-only extra', 'amount' => 200, 'add_to_budget' => false],
                ['label' => 'Zero extra', 'amount' => 0, 'add_to_budget' => true],
            ],
            'budget_wedding_planner_special_packages' => [
                ['label' => 'Selected package', 'amount' => 700, 'add_to_budget' => true],
                ['label' => 'Proposal-only package', 'amount' => 400, 'add_to_budget' => false],
                ['label' => 'Zero package', 'amount' => 0, 'add_to_budget' => true],
            ],
        ]);

        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame([
            'Selected extra',
            'Proposal-only extra',
            'Selected package',
            'Proposal-only package',
        ], collect($data['extra_rows'])->pluck('label')->all());
    }

    public static function zeroOrMissingExtraServices(): array
    {
        return [
            'zero amounts' => [[['label' => 'Zero extra', 'amount' => 0]]],
            'legacy null extras' => [null],
            'empty extras' => [[]],
        ];
    }

    #[DataProvider('zeroOrMissingExtraServices')]
    public function test_proposal_includes_base_price_list_and_positive_packages_when_extras_are_zero_or_missing(?array $extras): void
    {
        $lead = new Lead([
            'proposal_wedding_planning_service' => '<p>Planning support</p>',
            'budget_wedding_planner_extra_services' => $extras,
            'budget_wedding_planner_special_packages' => [
                ['label' => 'Special welcome dinner', 'amount' => 700, 'add_to_budget' => false],
                ['label' => 'Zero package', 'amount' => 0, 'add_to_budget' => true],
            ],
        ]);
        $before = $lead->getAttributes();
        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame([300, 100, 300, 300, 500, 800, 50, 700.0], array_column($data['extra_rows'], 'amount'));
        $this->assertSame('Management of guests accommodation out of the venue', $data['extra_rows'][0]['label']);
        $this->assertSame('Special welcome dinner', $data['extra_rows'][7]['label']);
        $this->assertNotContains('Zero extra', array_column($data['extra_rows'], 'label'));
        $this->assertNotContains('Zero package', array_column($data['extra_rows'], 'label'));
        $this->assertSame($before, $lead->getAttributes());
    }

    public function test_proposal_keeps_base_price_list_when_extras_and_packages_have_no_positive_amounts(): void
    {
        $lead = new Lead([
            'proposal_wedding_planning_service' => '<p>Planning support</p>',
            'budget_wedding_planner_extra_services' => [['label' => 'Zero extra', 'amount' => 0]],
            'budget_wedding_planner_special_packages' => [['label' => 'Zero package', 'amount' => 0]],
        ]);
        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame([300, 100, 300, 300, 500, 800, 50], array_column($data['extra_rows'], 'amount'));
    }

    public function test_proposal_title_includes_selected_venue_before_period(): void
    {
        $venue = Supplier::query()->create([
            'name' => 'Villa Aurora',
        ]);
        $lead = Lead::query()->create([
            'couple_name' => 'Anna and Marco',
            'wedding_period' => 'September 2027',
            'desired_region' => 'Tuscany',
            'venue_id' => $venue->id,
        ]);

        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame("WEDDING IN TUSCANY\nVILLA AURORA\nSEPTEMBER 2027", $data['proposal_title']);
    }

    public function test_proposal_title_uses_lead_wedding_date_when_period_is_empty(): void
    {
        $lead = new Lead([
            'wedding_date' => '2027-09-18',
            'desired_region' => 'Tuscany',
            'proposal_wedding_planning_service' => '<p>Planning support</p>',
        ]);

        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame("WEDDING IN TUSCANY\nSEPTEMBER 18, 2027", $data['proposal_title']);
    }

    public function test_proposal_uses_the_edited_confirmation_and_offer_validity_sections(): void
    {
        $lead = new Lead([
            'proposal_wedding_planning_service' => '<p>Planning support</p>',
            'proposal_content' => <<<'HTML'
                <h1>Proposal</h1>
                <h2>Conditions</h2>
                <p>To proceed with the confirmation:</p>
                <ul>
                    <li><p>Custom deposit condition.</p></li>
                    <li><p>Custom balance condition.</p></li>
                </ul>
                <h2>Offer validity</h2>
                <p>This offer is valid 10 days from today<br><strong>(until October 5th 2026)</strong>. After that limit, a new quote might apply.</p>
                <p><strong>No reservation has been made at this stage.</strong></p>
                HTML,
        ]);

        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame([
            'Custom deposit condition.',
            'Custom balance condition.',
        ], $data['confirmation_rows']);
        $this->assertSame([
            'This offer is valid 10 days from today (until October 5th 2026). After that limit, a new quote might apply.',
            'No reservation has been made at this stage.',
        ], $data['offer_validity_rows']);
    }

    public function test_proposal_uses_default_offer_validity_for_legacy_condition_content(): void
    {
        $this->travelTo('2026-09-25');

        $lead = new Lead([
            'proposal_wedding_planning_service' => '<p>Planning support</p>',
            'proposal_content' => '<ul><li>Custom condition.</li></ul>',
        ]);

        $data = $this->callProtected(new LeadProposalPdfController(), 'buildData', $lead);

        $this->assertSame(['Custom condition.'], $data['confirmation_rows']);
        $this->assertSame([
            'This offer is valid 30 days from today (until October 25th 2026). After that limit, a new quote might apply.',
            'No reservation has been made at this stage.',
        ], $data['offer_validity_rows']);
    }

    public function test_proposal_photos_are_center_cropped_to_their_frame_ratio(): void
    {
        $lead = new Lead([
            'proposal_images_json_config' => ['template' => 'lake_como'],
        ]);

        $images = $this->callProtected(new LeadProposalPdfController(), 'proposalImages', $lead);
        $expectedRatios = [
            'cover_bride' => 82 / 200.5,
            'cover_venue' => 82 / 171,
            'table_cypress' => 82 / 118.5,
            'ceremony_hills' => 82 / 119,
            'ceremony_view' => 75 / 104,
            'dinner_garden' => 75 / 84,
            'ceremony_altar' => 82 / 90,
            'table_white' => 82 / 91,
            'table_strip' => 90 / 66.15,
            'wedding_ceremony' => 85 / 75,
            'olive_ceremony' => 85 / 115,
            'table_film' => 87 / 94,
            'table_rustic' => 87 / 93,
        ];

        foreach ($expectedRatios as $key => $expectedRatio) {
            $this->assertStringStartsWith('data:image/jpeg;base64,', $images[$key]);
            $size = getimagesizefromstring(base64_decode(substr($images[$key], strlen('data:image/jpeg;base64,'))));

            $this->assertNotFalse($size);
            $this->assertEqualsWithDelta($expectedRatio, $size[0] / $size[1], 0.01, $key);
        }
    }

    protected function callProtected(object $object, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$arguments);
    }
}
