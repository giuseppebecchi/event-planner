<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\ProjectDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ViewProjectDocuments extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-documents';

    protected static ?string $breadcrumb = 'Documents';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return (string) $this->getRecordTitle();
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function addProjectDocumentAction(): Action
    {
        return Action::make('addProjectDocument')
            ->label('Add document')
            ->modalHeading('Add project document')
            ->modalSubmitActionLabel('Add document')
            ->visible(fn (): bool => ! auth()->user()?->isCustomer())
            ->form([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options(ProjectDocument::TYPE_OPTIONS)
                    ->default(ProjectDocument::TYPE_OTHER)
                    ->required(),
                FileUpload::make('file_path')
                    ->label('File')
                    ->disk('public')
                    ->directory('projects/documents')
                    ->maxSize(20480)
                    ->required(),
                Textarea::make('description')
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                if (auth()->user()?->isCustomer()) {
                    return;
                }

                $type = $data['type'] ?? ProjectDocument::TYPE_OTHER;

                $this->getRecord()->projectDocuments()->create([
                    'title' => trim((string) $data['title']),
                    'document_type' => $type,
                    'type' => $type,
                    'file_path' => $data['file_path'],
                    'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                ]);

                $this->getRecord()->unsetRelation('projectDocuments');

                Notification::make()
                    ->title('Project document added')
                    ->success()
                    ->send();
            });
    }

    public function getProjectDocuments(): Collection
    {
        return $this->documents()
            ->filter(fn (ProjectDocument $document): bool => blank($document->supplier_id) && blank($document->category_budget_supplier_id))
            ->values();
    }

    public function getSupplierDocuments(): Collection
    {
        return $this->documents()
            ->reject(fn (ProjectDocument $document): bool => blank($document->supplier_id) && blank($document->category_budget_supplier_id))
            ->values();
    }

    public function getSupplierDocumentGroups(): Collection
    {
        return $this->getSupplierDocuments()
            ->groupBy(fn (ProjectDocument $document): string => $this->documentSupplierName($document) ?: 'Supplier not set')
            ->map(fn (Collection $documents, string $supplierName): array => [
                'supplier_name' => $supplierName,
                'documents' => $documents->values(),
                'categories' => $documents
                    ->map(fn (ProjectDocument $document): ?string => $this->documentCategoryLabel($document))
                    ->filter()
                    ->unique()
                    ->values(),
            ])
            ->sortKeys()
            ->values();
    }

    public function getDocumentsSummary(): array
    {
        $projectDocuments = $this->getProjectDocuments();
        $supplierDocuments = $this->getSupplierDocuments();

        return [
            'project_documents_count' => $projectDocuments->count(),
            'supplier_documents_count' => $supplierDocuments->count(),
            'total_count' => $projectDocuments->count() + $supplierDocuments->count(),
            'suppliers_count' => $supplierDocuments
                ->map(fn (ProjectDocument $document): ?int => $this->documentSupplierId($document))
                ->filter()
                ->unique()
                ->count(),
        ];
    }

    protected function documents(): Collection
    {
        return $this->getRecord()
            ->projectDocuments()
            ->with(['supplier', 'categoryBudgetSupplier.supplier', 'categoryBudgetSupplier.category'])
            ->get()
            ->sortBy(fn (ProjectDocument $document): string => sprintf(
                '%s-%s-%s-%s',
                $this->documentSupplierName($document) ?: '000-project',
                $this->documentTypeLabel($document),
                $document->created_at?->format('YmdHis') ?? '00000000000000',
                mb_strtolower($document->title),
            ))
            ->values();
    }

    public function documentTypeLabel(ProjectDocument $document): string
    {
        return ProjectDocument::TYPE_OPTIONS[$document->type] ?? ucfirst(str_replace('_', ' ', (string) $document->type));
    }

    public function documentSupplierName(ProjectDocument $document): ?string
    {
        return $document->supplier?->name
            ?: $document->categoryBudgetSupplier?->supplier?->name;
    }

    public function documentCategoryLabel(ProjectDocument $document): ?string
    {
        return $document->categoryBudgetSupplier?->category?->label;
    }

    protected function documentSupplierId(ProjectDocument $document): ?int
    {
        return $document->supplier_id ?: $document->categoryBudgetSupplier?->supplier_id;
    }
}
