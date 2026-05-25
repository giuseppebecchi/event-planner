<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Guest;
use App\Models\ProjectSeatingPlan;
use App\Models\ProjectTable;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Renderless;

class AssignProjectLayout extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.assign-project-layout';

    protected static ?string $breadcrumb = 'Assign seating';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ProjectSeatingPlan $currentSeatingPlan;

    public function mount(int|string $record, int|string $seatingPlan): void
    {
        $this->record = $this->resolveRecord($record);
        $this->currentSeatingPlan = $this->getRecord()
            ->seatingPlans()
            ->with('tables')
            ->findOrFail($seatingPlan);
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

    public function getEditorTables(): array
    {
        return $this->currentSeatingPlan
            ->loadMissing('tables')
            ->tables
            ->map(fn (ProjectTable $table): array => [
                'id' => $table->id,
                'name' => $table->name ?: ('Table ' . $table->sort_order),
                'center_x' => (float) $table->center_x,
                'center_y' => (float) $table->center_y,
                'rotation' => (float) $table->rotation,
                'table_type' => $table->table_type,
                'primary_dimension' => (float) $table->primary_dimension,
                'secondary_dimension' => $table->secondary_dimension !== null ? (float) $table->secondary_dimension : (float) $table->primary_dimension,
                'seats_total' => $table->seats_total,
                'seats_by_side_json' => $table->seats_by_side_json ?? ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2],
                'guest_assignments_json' => $table->guest_assignments_json ?? [],
                'sort_order' => $table->sort_order,
            ])
            ->values()
            ->all();
    }

    public function getAssignableGuests(): array
    {
        return $this->getRecord()
            ->guests()
            ->orderBy('group_name')
            ->orderBy('primary_last_name')
            ->orderBy('primary_first_name')
            ->get()
            ->flatMap(fn (Guest $guest): array => $this->flattenGuestParty($guest))
            ->values()
            ->all();
    }

    public function getBackgroundImageUrl(): ?string
    {
        if (! $this->currentSeatingPlan->background_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->currentSeatingPlan->background_image_path);
    }

    public function getViewportState(): array
    {
        return [
            'zoom' => (float) ($this->currentSeatingPlan->viewport_zoom ?: 1),
            'x' => (int) ($this->currentSeatingPlan->viewport_x ?: 0),
            'y' => (int) ($this->currentSeatingPlan->viewport_y ?: 0),
        ];
    }

    #[Renderless]
    public function saveAssignments(array $tables): void
    {
        $validGuestKeys = collect($this->getAssignableGuests())->pluck('key')->all();

        $data = validator(
            ['tables' => $tables],
            [
                'tables' => ['array'],
                'tables.*.id' => ['required', 'integer', 'exists:project_tables,id'],
                'tables.*.guest_assignments_json' => ['nullable', 'array'],
                'tables.*.guest_assignments_json.*' => ['nullable', 'string', Rule::in($validGuestKeys)],
            ]
        )->validate();

        $allowedIds = $this->currentSeatingPlan->tables()->pluck('id')->all();
        $usedGuestKeys = [];

        foreach ($data['tables'] as $tableData) {
            if (! in_array((int) $tableData['id'], $allowedIds, true)) {
                continue;
            }

            $assignments = collect($tableData['guest_assignments_json'] ?? [])
                ->filter(fn ($guestKey): bool => filled($guestKey) && ! in_array($guestKey, $usedGuestKeys, true))
                ->map(function (string $guestKey) use (&$usedGuestKeys): string {
                    $usedGuestKeys[] = $guestKey;

                    return $guestKey;
                })
                ->all();

            ProjectTable::query()
                ->whereKey($tableData['id'])
                ->where('project_seating_plan_id', $this->currentSeatingPlan->id)
                ->update(['guest_assignments_json' => $assignments]);
        }

        $this->currentSeatingPlan->unsetRelation('tables');
    }

    protected function flattenGuestParty(Guest $guest): array
    {
        $people = [];

        if (filled($guest->primary_first_name) || filled($guest->primary_last_name)) {
            $people[] = $this->personPayload(
                'guest:' . $guest->id . ':primary',
                $guest->primary_first_name,
                $guest->primary_last_name,
                $guest->group_name ?: $guest->displayName(),
            );
        }

        if (filled($guest->partner_first_name) || filled($guest->partner_last_name)) {
            $people[] = $this->personPayload(
                'guest:' . $guest->id . ':partner',
                $guest->partner_first_name,
                $guest->partner_last_name,
                $guest->group_name ?: $guest->displayName(),
            );
        }

        foreach ($guest->normalizedAdditionalGuests() as $index => $additionalGuest) {
            if (blank($additionalGuest['first_name']) && blank($additionalGuest['last_name'])) {
                continue;
            }

            $people[] = $this->personPayload(
                'guest:' . $guest->id . ':additional:' . $index,
                $additionalGuest['first_name'],
                $additionalGuest['last_name'],
                $guest->group_name ?: $guest->displayName(),
            );
        }

        return $people;
    }

    protected function personPayload(string $key, ?string $firstName, ?string $lastName, string $group): array
    {
        $firstName = trim((string) $firstName);
        $lastName = trim((string) $lastName);
        $label = trim(collect([$firstName, $lastName])->filter()->implode(' '));
        $short = $lastName !== ''
            ? trim($lastName . ' ' . ($firstName !== '' ? mb_substr($firstName, 0, 1) . '.' : ''))
            : $label;

        return [
            'key' => $key,
            'label' => $label ?: 'Unnamed guest',
            'short' => $short ?: 'Guest',
            'group' => $group,
        ];
    }
}
