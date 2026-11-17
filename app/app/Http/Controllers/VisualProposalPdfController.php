<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ProposalTemplate;
use App\Support\ProposalConfiguration;
use App\Support\RichEditorHtmlNormalizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class VisualProposalPdfController extends LeadProposalPdfController
{
    public function leadPreview(Lead $lead)
    {
        $this->authorizeStaff();
        abort_unless($lead->proposal_layout, 404);

        return view('pdf.visual-proposal', $this->viewData($lead, ProposalConfiguration::forLead($lead), true));
    }

    public function templatePreview(ProposalTemplate $proposalTemplate)
    {
        $this->authorizeStaff();

        return view('pdf.visual-proposal', $this->viewData($this->exampleLead($proposalTemplate), $proposalTemplate->configuration, true));
    }

    public function templatePdf(ProposalTemplate $proposalTemplate)
    {
        $this->authorizeStaff();

        return Pdf::loadView('pdf.visual-proposal', $this->viewData($this->exampleLead($proposalTemplate), $proposalTemplate->configuration))
            ->setPaper('a4', 'landscape')->download(Str::slug($proposalTemplate->name).'-preview.pdf');
    }

    public function download(Lead $lead)
    {
        $this->authorizeStaff();

        return Pdf::loadView('pdf.visual-proposal', $this->viewData($lead, ProposalConfiguration::forLead($lead)))
            ->setPaper('a4', 'landscape')->download($this->filename($lead));
    }

    public function viewData(Lead $lead, array $configuration, bool $preview = false): array
    {
        if (! array_key_exists('planning_service_right', $configuration)) {
            $configuration = [...$configuration, ...ProposalConfiguration::splitPlanningService($configuration['planning_service'] ?? '')];
        }
        $renderLead = clone $lead;
        $renderLead->proposal_images_json_config = ['template' => $configuration['source_variant'] ?? 'tuscany'];
        $renderLead->proposal_wedding_planning_service = ProposalConfiguration::replaceVariables($configuration['planning_service'] ?? '', $lead);
        $renderLead->proposal_content = ProposalConfiguration::replaceVariables($configuration['conditions'] ?? '', $lead);
        $data = parent::buildData($renderLead);
        $rich = [];
        foreach (ProposalConfiguration::textFields() as $key => $label) {
            $rich[$key] = RichEditorHtmlNormalizer::preserveEmptyParagraphs(
                Str::sanitizeHtml(ProposalConfiguration::replaceVariables((string) ($configuration[$key] ?? ''), $lead))
            );
        }
        $ratios = ['cover_bride' => 82 / 200.5, 'cover_venue' => 82 / 171, 'table_cypress' => 82 / 118.5,
            'ceremony_hills' => 82 / 119, 'ceremony_view' => 75 / 104, 'dinner_garden' => 75 / 84,
            'ceremony_altar' => 82 / 90, 'table_white' => 82 / 91, 'table_strip' => 90 / 66.15,
            'wedding_ceremony' => 85 / 75, 'olive_ceremony' => 85 / 115, 'table_film' => 87 / 94, 'table_rustic' => 87 / 93];
        $images = [];
        foreach (ProposalConfiguration::imageFields() as $key => $definition) {
            $path = ProposalConfiguration::imagePath($configuration['images'][$key] ?? null);
            if (! $path) {
                $images[$key] = '';

                continue;
            }
            $image = isset($ratios[$key]) ? $this->centerCroppedImageDataUri($path, $ratios[$key]) : $path;
            $images[$key] = str_starts_with($image, 'data:') ? $image
                : 'data:'.mime_content_type($image).';base64,'.base64_encode(file_get_contents($image));
        }
        $fonts = ['title' => $preview ? asset('fonts/proposal/Montserrat-Regular.ttf') : public_path('fonts/proposal/Montserrat-Regular.ttf')];
        if ($preview) {
            foreach (['serif' => 'DejaVuSerif', 'sans' => 'DejaVuSans'] as $key => $font) {
                $fonts[$key] = 'data:font/ttf;base64,'.base64_encode(file_get_contents(base_path('vendor/dompdf/dompdf/lib/fonts/'.$font.'.ttf')));
            }
        }

        return ['lead' => $lead, 'data' => $data, 'rich' => $rich, 'images' => $images,
            'fonts' => $fonts,
            'venueName' => $this->venueName($lead), 'preview' => $preview];
    }

    protected function exampleLead(ProposalTemplate $template): Lead
    {
        return new Lead(['couple_name' => 'Sample couple', 'wedding_date' => '2027-09-18',
            'desired_region' => ($template->configuration['source_variant'] ?? '') === 'lake_como' ? 'Lake Como' : 'Tuscany']);
    }

    protected function authorizeStaff(): void
    {
        abort_unless(auth()->check() && ! auth()->user()->isCustomer(), 403);
    }
}
