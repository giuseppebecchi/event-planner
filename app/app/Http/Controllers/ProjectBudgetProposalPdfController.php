<?php

namespace App\Http\Controllers;

use App\Models\CategoryBudget;
use App\Models\CategoryBudgetSupplier;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectBudgetProposalPdfController extends Controller
{
    public function __invoke(Project $project, CategoryBudget $categoryBudget)
    {
        abort_if((int) $categoryBudget->project_id !== (int) $project->id, 404);

        $categoryBudget->loadMissing([
            'category',
            'supplierProposals.supplier.images',
            'supplierProposals.projectDocuments',
        ]);

        $shortlisted = $this->shortlistedProposals($categoryBudget);
        $proposals = $shortlisted->isNotEmpty()
            ? $shortlisted
            : $this->receivedProposals($categoryBudget);

        abort_if($proposals->isEmpty(), 404);

        $pdf = Pdf::loadView('pdf.project-budget-proposals', [
            'project' => $project,
            'budget' => $categoryBudget,
            'proposals' => $proposals,
            'usesFallback' => $shortlisted->isEmpty(),
            'dateRange' => $this->projectDateRangeLabel($project),
            'partners' => collect([$project->partner_one_name, $project->partner_two_name])->filter()->implode(' & '),
            'coverBackground' => $this->publicImageDataUri('images/bg.jpg'),
            'logo' => $this->publicImageDataUri('images/logo.png'),
            'imageResolver' => fn (?string $path): ?string => $this->storageImageDataUri($path),
            'galleryImageResolver' => fn (?string $path): ?string => $this->croppedStorageImageDataUri($path, 1200, 494),
            'money' => fn ($amount): string => $amount !== null ? 'EUR ' . number_format((float) $amount, 2, ',', '.') : '-',
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename($project, $categoryBudget));
    }

    protected function shortlistedProposals(CategoryBudget $budget): Collection
    {
        return $budget->supplierProposals
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->scouting_status === 'shortlist')
            ->values();
    }

    protected function receivedProposals(CategoryBudget $budget): Collection
    {
        return $budget->supplierProposals
            ->filter(fn (CategoryBudgetSupplier $proposal): bool => $proposal->hasResponse())
            ->values();
    }

    protected function publicImageDataUri(string $path): ?string
    {
        $fullPath = public_path($path);

        return $this->fileToDataUri($fullPath);
    }

    protected function storageImageDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return $this->fileToDataUri(Storage::disk('public')->path($path));
    }

    protected function croppedStorageImageDataUri(?string $path, int $targetWidth, int $targetHeight): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);

        if (! is_file($fullPath) || ! extension_loaded('gd')) {
            return $this->fileToDataUri($fullPath);
        }

        $source = @imagecreatefromstring((string) file_get_contents($fullPath));

        if (! $source) {
            return $this->fileToDataUri($fullPath);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $sourceRatio = $sourceWidth / max(1, $sourceHeight);
        $targetRatio = $targetWidth / max(1, $targetHeight);

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $cropX = (int) round(($sourceWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($sourceHeight - $cropHeight) / 2);
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        ob_start();
        imagejpeg($target, null, 88);
        $content = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $content ? 'data:image/jpeg;base64,' . base64_encode($content) : $this->fileToDataUri($fullPath);
    }

    protected function fileToDataUri(string $fullPath): ?string
    {
        if (! is_file($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($fullPath));
    }

    protected function filename(Project $project, CategoryBudget $budget): string
    {
        $parts = collect([
            $project->name,
            $budget->category?->label_it ?? 'proposals',
            'presentation',
        ])->filter()->implode(' ');

        return (Str::slug($parts) ?: 'proposal-presentation') . '.pdf';
    }

    protected function projectDateRangeLabel(Project $project): string
    {
        if (! $project->event_start_date) {
            return 'Date to be defined';
        }

        if (! $project->event_end_date || $project->event_end_date->isSameDay($project->event_start_date)) {
            return $project->event_start_date->format('F j, Y');
        }

        return sprintf(
            '%s - %s',
            $project->event_start_date->format('F j, Y'),
            $project->event_end_date->format('F j, Y'),
        );
    }
}
