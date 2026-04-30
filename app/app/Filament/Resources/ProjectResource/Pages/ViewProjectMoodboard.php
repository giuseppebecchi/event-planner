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
use Illuminate\Support\Facades\Storage;
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
        'notes' => '',
    ];

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
                    'images' => $board->images->sortByDesc('created_at')->values(),
                ];
            })
            ->values();
    }

    public function openBoardModal(string $presetTitle = ''): void
    {
        $this->boardForm = [
            'title' => $presetTitle,
            'notes' => '',
        ];
        $this->showBoardModal = true;
    }

    public function closeBoardModal(): void
    {
        $this->showBoardModal = false;
        $this->boardForm = [
            'title' => '',
            'notes' => '',
        ];
    }

    public function saveBoard(): void
    {
        $data = validator($this->boardForm, [
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $this->getRecord()->projectMoodboards()->create([
            'title' => trim((string) $data['title']),
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
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
}
