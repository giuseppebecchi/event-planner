<?php

namespace App\Http\Controllers;

use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ProjectBudgetComparisonPdfController extends Controller
{
    public function __invoke(Project $project, CategoryBudget $categoryBudget)
    {
        abort_if((int) $categoryBudget->project_id !== (int) $project->id, 404);

        $categoryBudget->loadMissing([
            'category',
            'supplierProposals.supplier',
        ]);

        $comparison = $this->comparison($categoryBudget);

        abort_if($comparison['proposals']->isEmpty(), 404);

        $pdf = Pdf::loadView('pdf.project-budget-comparison', [
            'project' => $project,
            'budget' => $categoryBudget,
            'proposals' => $comparison['proposals'],
            'rows' => $comparison['rows'],
            'totals' => $comparison['totals'],
            'money' => fn ($amount): string => $amount !== null && $amount !== '' ? 'EUR ' . number_format((float) $amount, 2, ',', '.') : '-',
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename($project, $categoryBudget));
    }

    protected function comparison(CategoryBudget $budget): array
    {
        $proposals = $budget
            ->supplierProposals
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => collect($proposal->cost_items_json ?? [])->contains(fn ($item): bool => is_array($item) && filled($item['label'] ?? null)))
            ->values();

        $rows = $proposals
            ->flatMap(fn (CategoryBudgetSupplier $proposal): array => $proposal->cost_items_json ?? [])
            ->map(fn ($item): string => trim((string) ($item['label'] ?? '')))
            ->filter()
            ->unique(fn (string $label): string => mb_strtolower($label))
            ->sort()
            ->values()
            ->map(function (string $label) use ($proposals): array {
                $key = mb_strtolower($label);

                return [
                    'label' => $label,
                    'amounts' => $proposals
                        ->mapWithKeys(function (CategoryBudgetSupplier $proposal) use ($key): array {
                            $item = collect($proposal->cost_items_json ?? [])
                                ->first(fn ($item): bool => mb_strtolower(trim((string) ($item['label'] ?? ''))) === $key);

                            return [$proposal->id => $item['amount'] ?? null];
                        })
                        ->all(),
                ];
            })
            ->all();

        return [
            'proposals' => $proposals,
            'rows' => $rows,
            'totals' => $proposals
                ->mapWithKeys(fn (CategoryBudgetSupplier $proposal): array => [
                    $proposal->id => collect($proposal->cost_items_json ?? [])
                        ->filter(fn ($item): bool => is_array($item) && filled($item['label'] ?? null) && filled($item['amount'] ?? null))
                        ->sum(fn (array $item): float => (float) $item['amount']),
                ])
                ->all(),
        ];
    }

    protected function filename(Project $project, CategoryBudget $budget): string
    {
        $parts = collect([
            $project->name,
            $budget->category?->label_it ?? 'budget',
            'comparison',
        ])->filter()->implode(' ');

        return (Str::slug($parts) ?: 'budget-comparison') . '.pdf';
    }
}
