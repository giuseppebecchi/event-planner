<?php

namespace Tests\Feature;

use App\Http\Controllers\LeadBudgetPdfController;
use App\Http\Controllers\LeadProposalPdfController;
use App\Models\Lead;
use App\Models\Supplier;
use App\Support\LeadContractPdfRenderer;
use ReflectionMethod;
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

    protected function callProtected(object $object, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$arguments);
    }
}
