<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Project;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class ManageProjectRsvpConfiguration extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.manage-project-rsvp-configuration';

    protected static ?string $breadcrumb = 'RSVP configuration';

    protected Width|string|null $maxContentWidth = Width::Full;

    public array $fields = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if(auth()->user()?->isCustomer(), 403);

        $this->fields = $this->normalizeFields($this->getRecord()->rsvpConfigurationFields());
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

    public function addField(): void
    {
        $this->fields[] = [
            'key' => 'custom_' . Str::uuid()->toString(),
            'enabled' => true,
            'label' => 'Custom question',
            'help_text' => '',
            'type' => 'text',
            'response_scope' => 'aggregate',
            'options_text' => '',
            'order' => count($this->fields) + 1,
        ];

        $this->reindexFieldOrder();
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
        $this->reindexFieldOrder();
    }

    public function moveRsvpField(int|string|null $from, int|string|null $to): void
    {
        $from = is_numeric($from) ? (int) $from : null;
        $to = is_numeric($to) ? (int) $to : null;

        if ($from === null || $to === null || $from === $to || ! isset($this->fields[$from], $this->fields[$to])) {
            return;
        }

        $field = $this->fields[$from];
        array_splice($this->fields, $from, 1);
        array_splice($this->fields, $to, 0, [$field]);
        $this->reindexFieldOrder();
    }

    public function resetDefaults(): void
    {
        $this->fields = $this->normalizeFields(Project::defaultRsvpConfiguration()['fields']);
    }

    public function saveRsvpConfiguration(): void
    {
        $data = validator(
            ['fields' => $this->fields],
            [
                'fields' => ['array'],
                'fields.*.key' => ['required', 'string'],
                'fields.*.enabled' => ['boolean'],
                'fields.*.label' => ['required', 'string', 'max:255'],
                'fields.*.help_text' => ['nullable', 'string', 'max:1000'],
                'fields.*.type' => ['required', 'in:text,select,checkbox'],
                'fields.*.response_scope' => ['required', 'in:aggregate,per_guest'],
                'fields.*.options_text' => ['nullable', 'string'],
            ]
        )->validate();

        $fields = collect($data['fields'])
            ->values()
            ->map(function (array $field, int $index): array {
                $type = $field['type'] ?? 'text';

                return [
                    'key' => trim((string) $field['key']),
                    'enabled' => (bool) ($field['enabled'] ?? false),
                    'label' => trim((string) $field['label']),
                    'help_text' => trim((string) ($field['help_text'] ?? '')),
                    'type' => $type,
                    'response_scope' => $field['response_scope'] ?? 'aggregate',
                    'options' => $type === 'select'
                        ? $this->splitOptions((string) ($field['options_text'] ?? ''))
                        : [],
                    'order' => $index + 1,
                ];
            })
            ->all();

        $this->getRecord()->forceFill([
            'rsvp_configuration' => ['fields' => $fields],
        ])->save();

        $this->fields = $this->normalizeFields($fields);

        Notification::make()
            ->title('RSVP configuration saved')
            ->success()
            ->send();
    }

    protected function normalizeFields(array $fields): array
    {
        return collect($fields)
            ->values()
            ->map(fn (array $field, int $index): array => [
                'key' => trim((string) ($field['key'] ?? 'custom_' . Str::uuid()->toString())),
                'enabled' => (bool) ($field['enabled'] ?? false),
                'label' => trim((string) ($field['label'] ?? 'RSVP field')),
                'help_text' => trim((string) ($field['help_text'] ?? '')),
                'type' => in_array(($field['type'] ?? 'text'), ['text', 'select', 'checkbox'], true) ? $field['type'] : 'text',
                'response_scope' => in_array(($field['response_scope'] ?? null), ['aggregate', 'per_guest'], true)
                    ? $field['response_scope']
                    : (($field['key'] ?? null) === 'food_allergies' ? 'per_guest' : 'aggregate'),
                'options_text' => implode("\n", is_array($field['options'] ?? null) ? $field['options'] : []),
                'order' => $index + 1,
            ])
            ->all();
    }

    protected function reindexFieldOrder(): void
    {
        $this->fields = collect($this->fields)
            ->values()
            ->map(function (array $field, int $index): array {
                $field['order'] = $index + 1;

                return $field;
            })
            ->all();
    }

    protected function splitOptions(string $options): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $options) ?: [])
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->values()
            ->all();
    }
}
