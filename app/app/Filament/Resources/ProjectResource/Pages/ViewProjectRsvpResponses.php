<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Guest;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewProjectRsvpResponses extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-rsvp-responses';

    protected static ?string $breadcrumb = 'RSVP responses';

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

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getGuests(): Collection
    {
        return $this->getRecord()
            ->guests()
            ->orderBy('rsvp_number')
            ->get();
    }

    public function getFields(): array
    {
        return collect($this->getRecord()->rsvpConfigurationFields())
            ->where('enabled', true)
            ->values()
            ->all();
    }

    public function getSummary(): array
    {
        $guests = $this->getGuests();
        $completed = $guests->whereNotNull('rsvp_completed_at')->count();
        $guestRows = $this->getGuestRows();

        return [
            'completed' => $completed,
            'total_parties' => $guests->count(),
            'confirmed_people' => $guestRows
                ->filter(fn (array $row): bool => (bool) $row['completed'] && (bool) $row['is_attending'])
                ->count(),
        ];
    }

    public function getGuestGroups(): Collection
    {
        return $this->getGuests()
            ->map(fn (Guest $guest): array => [
                'guest' => $guest,
                'label' => $guest->displayName(),
                'rsvp_number' => $guest->rsvp_number,
                'completed' => (bool) $guest->rsvp_completed_at,
                'rows' => $this->personRowsForGuest($guest),
            ]);
    }

    public function getGuestRows(): Collection
    {
        return $this->getGuestGroups()
            ->flatMap(fn (array $group): array => $group['rows'])
            ->values();
    }

    public function personRowsForGuest(Guest $guest): array
    {
        $rows = [
            $this->makePersonRow(
                $guest,
                'primary',
                'Primary guest',
                $guest->primary_first_name,
                $guest->primary_last_name,
                $guest->primary_role
            ),
        ];

        if (
            filled($guest->partner_first_name)
            || filled($guest->partner_last_name)
            || (bool) $guest->unspecified_plus_one
        ) {
            $rows[] = $this->makePersonRow(
                $guest,
                'partner',
                'Partner / Plus-one',
                $guest->partner_first_name,
                $guest->partner_last_name,
                $guest->partner_role
            );
        }

        foreach ($guest->normalizedAdditionalGuests()->values() as $index => $additionalGuest) {
            $rows[] = $this->makePersonRow(
                $guest,
                'additional_' . $index,
                'Additional guest',
                $additionalGuest['first_name'] ?? '',
                $additionalGuest['last_name'] ?? '',
                $additionalGuest['role'] ?: ($additionalGuest['type'] ?? '')
            );
        }

        return $rows;
    }

    public function formatFieldResponseForRow(array $row, array $field): string
    {
        $guest = $row['guest'];
        $value = ($guest->rsvp_response ?? [])[$field['key']] ?? '';

        if (($field['response_scope'] ?? 'aggregate') === 'per_guest') {
            $entry = is_array($value) ? ($value[$row['subject_key']] ?? []) : [];
            $value = is_array($entry) ? ($entry['value'] ?? '') : $entry;
        }

        return $this->formatValue($value);
    }

    public function formatAttendanceForRow(array $row): string
    {
        if (! $row['completed']) {
            return '';
        }

        return $row['is_attending'] ? 'Yes' : 'No';
    }

    public function formatFieldResponse(Guest $guest, array $field): string
    {
        $value = ($guest->rsvp_response ?? [])[$field['key']] ?? '';

        if (($field['response_scope'] ?? 'aggregate') === 'per_guest' && is_array($value)) {
            return collect($value)
                ->map(function ($entry): string {
                    if (! is_array($entry)) {
                        return (string) $entry;
                    }

                    $entryValue = $entry['value'] ?? '';

                    if (is_bool($entryValue)) {
                        $entryValue = $entryValue ? 'Yes' : 'No';
                    }

                    return trim((string) ($entry['label'] ?? 'Guest')) . ': ' . (string) $entryValue;
                })
                ->filter(fn (string $entry): bool => trim($entry) !== ':')
                ->implode(' | ');
        }

        return $this->formatValue($value);
    }

    public function getResponseSummaries(): array
    {
        return collect($this->getFields())
            ->filter(fn (array $field): bool => in_array($field['type'] ?? 'text', ['checkbox', 'select'], true))
            ->map(function (array $field): array {
                $counts = collect();

                if (($field['response_scope'] ?? 'aggregate') === 'per_guest') {
                    foreach ($this->getGuestRows() as $row) {
                        if (! $row['completed']) {
                            continue;
                        }

                        $value = $this->rawFieldValueForRow($row, $field);

                        if ($this->isEmptyResponseValue($value)) {
                            continue;
                        }

                        $label = $this->formatValue($value);
                        $counts->put($label, ((int) $counts->get($label, 0)) + 1);
                    }
                } else {
                    foreach ($this->getGuests() as $guest) {
                        if (! $guest->rsvp_completed_at) {
                            continue;
                        }

                        $value = ($guest->rsvp_response ?? [])[$field['key']] ?? null;

                        if ($this->isEmptyResponseValue($value)) {
                            continue;
                        }

                        $label = $this->formatValue($value);
                        $counts->put($label, ((int) $counts->get($label, 0)) + 1);
                    }
                }

                if (($field['type'] ?? 'text') === 'checkbox') {
                    $counts = collect([
                        'Yes' => (int) $counts->get('Yes', 0),
                        'No' => (int) $counts->get('No', 0),
                    ]);
                }

                if (($field['type'] ?? 'text') === 'select') {
                    $counts = collect($field['options'] ?? [])
                        ->mapWithKeys(fn (string $option): array => [$option => (int) $counts->get($option, 0)])
                        ->merge($counts);
                }

                return [
                    'label' => $field['label'],
                    'type' => $field['type'] ?? 'text',
                    'scope' => $field['response_scope'] ?? 'aggregate',
                    'items' => $counts
                        ->map(fn (int $count, string|int $label): array => [
                            'label' => (string) $label,
                            'count' => $count,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function downloadGuestsPdf(): StreamedResponse
    {
        $project = $this->getRecord();
        $pdf = Pdf::loadView('pdf.project-guests', [
            'project' => $project,
            'groups' => $this->getGuestGroups(),
            'fields' => $this->getFields(),
            'summaries' => $this->getResponseSummaries(),
            'page' => $this,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            sprintf('%s-guests.pdf', str($project->name)->slug()->value() ?: 'guests'),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportRsvpResponses(): StreamedResponse
    {
        $project = $this->getRecord();
        $fields = $this->getFields();
        $groups = $this->getGuestGroups();
        $summaries = $this->getResponseSummaries();
        $filename = sprintf('%s-rsvp-responses.xlsx', str($project->name)->slug()->value() ?: 'rsvp');

        return response()->streamDownload(function () use ($fields, $groups, $summaries): void {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(array_merge([
                'RSVP #',
                'Family / group',
                'Guest name',
                'Role',
                'Confirmed',
                'Completed at',
                'Ceremony',
                'Reception',
                'Email',
                'Phone',
            ], collect($fields)->pluck('label')->all())));

            foreach ($groups as $group) {
                foreach ($group['rows'] as $row) {
                    $guest = $row['guest'];

                    $writer->addRow(Row::fromValues(array_merge([
                        $guest->rsvp_number,
                        $group['label'],
                        $row['name'],
                        $row['role'],
                        $this->formatAttendanceForRow($row),
                        $guest->rsvp_completed_at?->format('Y-m-d H:i'),
                        $this->formatStatus((int) $guest->ceremony),
                        $this->formatStatus((int) $guest->reception),
                        $guest->email,
                        $guest->phone,
                    ], collect($fields)->map(fn (array $field): string => $this->formatFieldResponseForRow($row, $field))->all())));
                }
            }

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Response summary']));

            foreach ($summaries as $summary) {
                $writer->addRow(Row::fromValues([$summary['label'], $summary['scope']]));

                foreach ($summary['items'] as $item) {
                    $writer->addRow(Row::fromValues(['', $item['label'], $item['count']]));
                }
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function formatStatus(int $status): string
    {
        return match ($status) {
            1 => 'Yes',
            -1 => 'No',
            default => '',
        };
    }

    protected function makePersonRow(
        Guest $guest,
        string $subjectKey,
        string $role,
        ?string $firstName,
        ?string $lastName,
        ?string $guestRole = null
    ): array {
        $firstName = trim((string) $firstName);
        $lastName = trim((string) $lastName);
        $name = trim(collect([$firstName, $lastName])->filter()->implode(' '));

        return [
            'guest' => $guest,
            'subject_key' => $subjectKey,
            'name' => $name ?: $role,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => filled($guestRole) ? trim((string) $guestRole) : $role,
            'completed' => (bool) $guest->rsvp_completed_at,
            'is_attending' => filled($lastName),
        ];
    }

    protected function rawFieldValueForRow(array $row, array $field): mixed
    {
        $guest = $row['guest'];
        $value = ($guest->rsvp_response ?? [])[$field['key']] ?? null;

        if (($field['response_scope'] ?? 'aggregate') !== 'per_guest') {
            return $value;
        }

        $entry = is_array($value) ? ($value[$row['subject_key']] ?? null) : null;

        return is_array($entry) ? ($entry['value'] ?? null) : $entry;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->map(fn (mixed $item): string => $this->formatValue($item))
                ->filter()
                ->implode(', ');
        }

        return trim((string) $value);
    }

    protected function isEmptyResponseValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return false;
        }

        return blank($value);
    }
}
