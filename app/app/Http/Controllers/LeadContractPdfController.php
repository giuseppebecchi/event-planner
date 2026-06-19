<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Support\LeadContractPdfRenderer;

class LeadContractPdfController extends Controller
{
    public function __invoke(Lead $lead, LeadContractPdfRenderer $renderer)
    {
        return response($renderer->output($lead), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$renderer->filename($lead).'"',
        ]);
    }
}
