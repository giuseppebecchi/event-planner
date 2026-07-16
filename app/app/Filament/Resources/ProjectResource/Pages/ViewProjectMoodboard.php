<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\ProjectImage;
use App\Models\ProjectMoodboard;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class ViewProjectMoodboard extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-moodboard';

    protected static ?string $breadcrumb = 'Moodboard';

    protected Width|string|null $maxContentWidth = Width::Full;

    public bool $showBoardModal = false;
    public bool $showImageModal = false;
    public ?int $deleteImageId = null;
    public ?int $deleteBoardId = null;

    public array $boardForm = [
        'title' => '',
        'source_type' => ProjectMoodboard::SOURCE_UPLOAD,
        'pinterest_board_url' => '',
        'notes' => '',
    ];
    public $boardPdfUpload = null;

    public array $imageForm = [
        'description' => '',
        'image_category' => 'details',
        'is_client_visible' => false,
    ];

    public string $imageTargetType = 'custom';
    public ?int $imageTargetId = null;
    public array $imageUploads = [];

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

    public function getSupplierBoards(): Collection
    {
        return $this->getRecord()
            ->loadMissing('projectImages.supplier.category')
            ->projectImages
            ->whereNotNull('supplier_id')
            ->sortByDesc('created_at')
            ->groupBy('supplier_id')
            ->map(function (Collection $images, int|string $supplierId): array {
                /** @var \App\Models\ProjectImage $first */
                $first = $images->first();
                $supplier = $first?->supplier;

                return [
                    'key' => 'supplier-' . $supplierId,
                    'type' => 'supplier',
                    'id' => (int) $supplierId,
                    'title' => $supplier?->name ?? 'Supplier board',
                    'subtitle' => $supplier?->category?->label_it ?? ($supplier?->category?->label ?? 'Supplier references'),
                    'accent' => '#9d8451',
                    'images' => $images->values(),
                ];
            })
            ->sortBy(fn (array $board): string => mb_strtolower($board['title']))
            ->values();
    }

    public function getCustomBoards(): Collection
    {
        return $this->getRecord()
            ->loadMissing('projectMoodboards.images')
            ->projectMoodboards
            ->sortBy([['sort_order', 'asc'], ['title', 'asc']])
            ->map(function (ProjectMoodboard $board): array {
                $accent = match (mb_strtolower($board->title)) {
                    'makeup', 'beauty' => '#c57c8b',
                    'florals', 'flowers' => '#62875c',
                    'tablescape', 'table setting', 'apparecchiatura' => '#6a7f94',
                    default => '#8b6c51',
                };

                return [
                    'key' => 'custom-' . $board->id,
                    'type' => 'custom',
                    'id' => $board->id,
                    'title' => $board->title,
                    'subtitle' => $board->notes ?: 'Custom inspiration board',
                    'accent' => $accent,
                    'source_type' => $board->source_type ?: ProjectMoodboard::SOURCE_UPLOAD,
                    'pinterest_board_url' => $board->pinterest_board_url,
                    'pdf_file_path' => $board->pdf_file_path,
                    'pdf_original_name' => $board->pdf_original_name,
                    'pdf_url' => $board->pdf_file_path ? Storage::disk('public')->url($board->pdf_file_path) : null,
                    'images' => $board->images->sortByDesc('created_at')->values(),
                ];
            })
            ->values();
    }

    public function openBoardModal(string $presetTitle = ''): void
    {
        $this->boardForm = [
            'title' => $presetTitle,
            'source_type' => ProjectMoodboard::SOURCE_UPLOAD,
            'pinterest_board_url' => '',
            'notes' => '',
        ];
        $this->boardPdfUpload = null;
        $this->showBoardModal = true;
    }

    public function closeBoardModal(): void
    {
        $this->showBoardModal = false;
        $this->boardForm = [
            'title' => '',
            'source_type' => ProjectMoodboard::SOURCE_UPLOAD,
            'pinterest_board_url' => '',
            'notes' => '',
        ];
        $this->boardPdfUpload = null;
    }

    public function saveBoard(): void
    {
        $data = validator(
            ['boardForm' => $this->boardForm, 'boardPdfUpload' => $this->boardPdfUpload],
            [
                'boardForm.title' => ['required', 'string', 'max:255'],
                'boardForm.source_type' => ['required', 'string', 'in:' . implode(',', [
                    ProjectMoodboard::SOURCE_UPLOAD,
                    ProjectMoodboard::SOURCE_PINTEREST,
                    ProjectMoodboard::SOURCE_PDF,
                ])],
                'boardForm.pinterest_board_url' => ['nullable', 'required_if:boardForm.source_type,' . ProjectMoodboard::SOURCE_PINTEREST, 'url', 'max:255'],
                'boardForm.notes' => ['nullable', 'string'],
                'boardPdfUpload' => ['nullable', 'required_if:boardForm.source_type,' . ProjectMoodboard::SOURCE_PDF, 'file', 'mimes:pdf', 'max:51200'],
            ],
            attributes: [
                'boardForm.title' => 'title',
                'boardForm.source_type' => 'source type',
                'boardForm.pinterest_board_url' => 'Pinterest board URL',
                'boardForm.notes' => 'notes',
                'boardPdfUpload' => 'PDF file',
            ],
        )->validate();

        $sourceType = $data['boardForm']['source_type'];
        $pinterestBoardUrl = $sourceType === ProjectMoodboard::SOURCE_PINTEREST
            ? $this->normalizePinterestBoardUrl((string) $data['boardForm']['pinterest_board_url'])
            : null;
        $pdfFilePath = null;
        $pdfOriginalName = null;

        if (
            $sourceType === ProjectMoodboard::SOURCE_PINTEREST
            && ! $this->isPinterestBoardUrl((string) $pinterestBoardUrl)
        ) {
            throw ValidationException::withMessages([
                'boardForm.pinterest_board_url' => 'Paste a public Pinterest board URL.',
            ]);
        }

        if ($sourceType === ProjectMoodboard::SOURCE_PDF) {
            $pdfFilePath = $this->boardPdfUpload->store('projects/moodboards', 'public');
            $pdfOriginalName = $this->boardPdfUpload->getClientOriginalName();
        }

        $this->getRecord()->projectMoodboards()->create([
            'title' => trim((string) $data['boardForm']['title']),
            'source_type' => $sourceType,
            'pinterest_board_url' => $pinterestBoardUrl,
            'pdf_file_path' => $pdfFilePath,
            'pdf_original_name' => $pdfOriginalName,
            'notes' => filled($data['boardForm']['notes'] ?? null) ? trim((string) $data['boardForm']['notes']) : null,
            'board_type' => 'custom',
            'sort_order' => ((int) $this->getRecord()->projectMoodboards()->max('sort_order')) + 1,
        ]);

        $this->getRecord()->unsetRelation('projectMoodboards');
        $this->closeBoardModal();

        Notification::make()
            ->title('Moodboard created')
            ->success()
            ->send();
    }

    public function openImageModal(string $targetType, int $targetId): void
    {
        if (! in_array($targetType, ['supplier', 'custom'], true)) {
            return;
        }

        if ($targetType === 'custom') {
            $board = $this->getRecord()->projectMoodboards()->find($targetId);

            if (in_array($board?->source_type, [ProjectMoodboard::SOURCE_PINTEREST, ProjectMoodboard::SOURCE_PDF], true)) {
                return;
            }
        }

        $this->imageTargetType = $targetType;
        $this->imageTargetId = $targetId;
        $this->imageForm = [
            'description' => '',
            'image_category' => 'details',
            'is_client_visible' => false,
        ];
        $this->imageUploads = [];
        $this->showImageModal = true;
    }

    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->imageTargetType = 'custom';
        $this->imageTargetId = null;
        $this->imageUploads = [];
        $this->imageForm = [
            'description' => '',
            'image_category' => 'details',
            'is_client_visible' => false,
        ];
    }

    public function saveImage(): void
    {
        $data = validator(
            ['form' => $this->imageForm, 'uploads' => $this->imageUploads],
            [
                'form.description' => ['nullable', 'string'],
                'form.image_category' => ['required', 'string', 'max:255'],
                'form.is_client_visible' => ['boolean'],
                'uploads' => ['required', 'array', 'min:1'],
                'uploads.*' => ['image', 'max:20480'],
            ]
        )->validate();

        if (! $this->imageTargetId) {
            return;
        }

        $basePayload = [
            'description' => filled($data['form']['description'] ?? null) ? trim((string) $data['form']['description']) : null,
            'image_category' => trim((string) $data['form']['image_category']),
            'is_client_visible' => (bool) ($data['form']['is_client_visible'] ?? false),
            'supplier_id' => null,
            'project_moodboard_id' => null,
        ];

        if ($this->imageTargetType === 'supplier') {
            $basePayload['supplier_id'] = $this->imageTargetId;
        } else {
            $basePayload['project_moodboard_id'] = $this->imageTargetId;
        }

        $uploadsCount = count($this->imageUploads);

        foreach ($this->imageUploads as $upload) {
            $this->getRecord()->projectImages()->create([
                ...$basePayload,
                'image_path' => $upload->store('projects/images', 'public'),
            ]);
        }

        $this->refreshMoodboardContext();
        $this->closeImageModal();

        Notification::make()
            ->title($uploadsCount > 1 ? 'Images added' : 'Image added')
            ->success()
            ->send();
    }

    public function promptDeleteImage(int $imageId): void
    {
        $this->deleteImageId = $imageId;
    }

    public function cancelDeleteImage(): void
    {
        $this->deleteImageId = null;
    }

    public function confirmDeleteImage(): void
    {
        if (! $this->deleteImageId) {
            return;
        }

        /** @var ProjectImage $image */
        $image = $this->getRecord()->projectImages()->findOrFail($this->deleteImageId);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        $this->deleteImageId = null;
        $this->refreshMoodboardContext();

        Notification::make()
            ->title('Image deleted')
            ->success()
            ->send();
    }

    public function promptDeleteBoard(int $boardId): void
    {
        $this->deleteBoardId = $boardId;
    }

    public function cancelDeleteBoard(): void
    {
        $this->deleteBoardId = null;
    }

    public function confirmDeleteBoard(): void
    {
        if (! $this->deleteBoardId) {
            return;
        }

        /** @var ProjectMoodboard $board */
        $board = $this->getRecord()->projectMoodboards()->with('images')->findOrFail($this->deleteBoardId);

        foreach ($board->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        if ($board->pdf_file_path) {
            Storage::disk('public')->delete($board->pdf_file_path);
        }

        $board->delete();
        $this->deleteBoardId = null;
        $this->refreshMoodboardContext();

        Notification::make()
            ->title('Moodboard deleted')
            ->success()
            ->send();
    }

    protected function refreshMoodboardContext(): void
    {
        $this->record = $this->resolveRecord($this->getRecord()->getKey());
        $this->getRecord()->unsetRelation('projectImages');
        $this->getRecord()->unsetRelation('projectMoodboards');
    }

    protected function normalizePinterestBoardUrl(string $url): string
    {
        $url = $this->resolvePinterestUrl(trim($url));
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'www.pinterest.com';
        $path = rtrim($parts['path'] ?? '', '/') . '/';
        $query = filled($parts['query'] ?? null) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . $path . $query;
    }

    protected function resolvePinterestUrl(string $url): string
    {
        $host = mb_strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));

        if ($host !== 'pin.it') {
            return $url;
        }

        $effectiveUrl = $url;

        try {
            Http::timeout(8)
                ->withOptions([
                    'allow_redirects' => ['max' => 5],
                    'on_stats' => function ($stats) use (&$effectiveUrl): void {
                        if (method_exists($stats, 'getEffectiveUri')) {
                            $effectiveUrl = (string) $stats->getEffectiveUri();
                        }
                    },
                ])
                ->get($url);
        } catch (\Throwable) {
            return $url;
        }

        return $effectiveUrl;
    }

    protected function isPinterestBoardUrl(string $url): bool
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return false;
        }

        $host = mb_strtolower((string) ($parts['host'] ?? ''));

        if (! str_contains($host, 'pinterest.')) {
            return false;
        }

        $segments = collect(explode('/', trim((string) ($parts['path'] ?? ''), '/')))
            ->filter()
            ->values();

        if ($segments->count() < 2) {
            return false;
        }

        return ! in_array(mb_strtolower((string) $segments->first()), ['pin', 'search', 'ideas', 'today'], true);
    }
}
