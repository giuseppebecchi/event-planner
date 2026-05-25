<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Guest;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Options as XlsxReaderOptions;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewProjectGuests extends Page
{
    use InteractsWithRecord;
    use InteractsWithProjectDateEditor;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-project-guests';

    protected static ?string $breadcrumb = 'Guests';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected const STATUS_FIELDS = [
        'invite_sent',
        'ceremony',
        'reception',
        'out_of_town',
        'gift_received',
        'thank_you_sent',
    ];

    public bool $showGuestEditor = false;

    public bool $showImportPanel = false;

    public ?int $editingGuestId = null;

    public ?int $confirmDeleteGuestId = null;

    public array $guestForm = [];

    public array $importOptions = [
        'replace_existing' => false,
    ];

    public ?TemporaryUploadedFile $guestImportFile = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->resetGuestForm();
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

    public function getGuests(): Collection
    {
        return $this->getRecord()
            ->loadMissing('guests')
            ->guests
            ->sortBy([
                ['guest_list', 'asc'],
                ['group_name', 'asc'],
                ['primary_last_name', 'asc'],
                ['primary_first_name', 'asc'],
                ['rsvp_number', 'asc'],
            ])
            ->values();
    }

    public function getGuestSummary(): array
    {
        $guests = $this->getGuests();

        return [
            'parties' => $guests->count(),
            'people' => $guests->sum(fn (Guest $guest): int => $guest->partySize()),
            'invited' => $guests->where('invite_sent', 1)->count(),
            'groups' => $guests->pluck('group_name')->filter()->unique()->count(),
        ];
    }

    public function startCreateGuest(): void
    {
        $this->editingGuestId = null;
        $this->resetGuestForm();
        $this->guestForm['rsvp_number'] = $this->nextRsvpNumber();
        $this->showGuestEditor = true;
    }

    public function editGuest(int $guestId): void
    {
        $guest = $this->findGuest($guestId);
        $this->editingGuestId = $guest->id;
        $this->showGuestEditor = true;
        $this->guestForm = [
            'rsvp_number' => $guest->rsvp_number,
            'guest_list' => $guest->guest_list ?? 'A List',
            'group_name' => $guest->group_name ?? '',
            'primary_title' => $guest->primary_title ?? '',
            'primary_first_name' => $guest->primary_first_name ?? '',
            'primary_last_name' => $guest->primary_last_name ?? '',
            'primary_suffix' => $guest->primary_suffix ?? '',
            'primary_role' => $guest->primary_role ?? '',
            'primary_gender' => $guest->primary_gender ?? '',
            'partner_title' => $guest->partner_title ?? '',
            'partner_first_name' => $guest->partner_first_name ?? '',
            'partner_last_name' => $guest->partner_last_name ?? '',
            'partner_suffix' => $guest->partner_suffix ?? '',
            'partner_role' => $guest->partner_role ?? '',
            'partner_gender' => $guest->partner_gender ?? '',
            'unspecified_plus_one' => (bool) $guest->unspecified_plus_one,
            'additional_guests' => $guest->normalizedAdditionalGuests()->all(),
            'formal_addressing' => $guest->formal_addressing ?? '',
            'address_line_1' => $guest->address_line_1 ?? '',
            'address_line_2' => $guest->address_line_2 ?? '',
            'city' => $guest->city ?? '',
            'state' => $guest->state ?? '',
            'postal_code' => $guest->postal_code ?? '',
            'country' => $guest->country ?? '',
            'phone' => $guest->phone ?? '',
            'email' => $guest->email ?? '',
            'invite_sent' => (int) $guest->invite_sent === 1,
            'ceremony' => (int) $guest->ceremony === 1,
            'reception' => (int) $guest->reception === 1,
            'out_of_town' => (int) $guest->out_of_town === 1,
            'gift_received' => (int) $guest->gift_received === 1,
            'thank_you_sent' => (int) $guest->thank_you_sent === 1,
            'notes' => $guest->notes ?? '',
        ];
    }

    public function closeGuestEditor(): void
    {
        $this->showGuestEditor = false;
        $this->editingGuestId = null;
        $this->resetGuestForm();
    }

    public function addAdditionalGuest(): void
    {
        $this->guestForm['additional_guests'][] = [
            'first_name' => '',
            'last_name' => '',
            'role' => '',
            'type' => 'Child',
            'age' => '',
            'gender' => '',
        ];
    }

    public function removeAdditionalGuest(int $index): void
    {
        unset($this->guestForm['additional_guests'][$index]);
        $this->guestForm['additional_guests'] = array_values($this->guestForm['additional_guests'] ?? []);
    }

    public function saveGuest(): void
    {
        $data = validator($this->guestForm, [
            'rsvp_number' => ['nullable', 'integer', 'min:1'],
            'guest_list' => ['nullable', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'primary_title' => ['nullable', 'string', 'max:50'],
            'primary_first_name' => ['required', 'string', 'max:255'],
            'primary_last_name' => ['nullable', 'string', 'max:255'],
            'primary_suffix' => ['nullable', 'string', 'max:50'],
            'primary_role' => ['nullable', 'string', 'max:255'],
            'primary_gender' => ['nullable', 'string', 'max:20'],
            'partner_title' => ['nullable', 'string', 'max:50'],
            'partner_first_name' => ['nullable', 'string', 'max:255'],
            'partner_last_name' => ['nullable', 'string', 'max:255'],
            'partner_suffix' => ['nullable', 'string', 'max:50'],
            'partner_role' => ['nullable', 'string', 'max:255'],
            'partner_gender' => ['nullable', 'string', 'max:20'],
            'unspecified_plus_one' => ['boolean'],
            'additional_guests' => ['array'],
            'additional_guests.*.first_name' => ['nullable', 'string', 'max:255'],
            'additional_guests.*.last_name' => ['nullable', 'string', 'max:255'],
            'additional_guests.*.role' => ['nullable', 'string', 'max:255'],
            'additional_guests.*.type' => ['nullable', 'string', 'max:50'],
            'additional_guests.*.age' => ['nullable', 'integer', 'min:0', 'max:18'],
            'additional_guests.*.gender' => ['nullable', 'string', 'max:20'],
            'formal_addressing' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'invite_sent' => ['boolean'],
            'ceremony' => ['boolean'],
            'reception' => ['boolean'],
            'out_of_town' => ['boolean'],
            'gift_received' => ['boolean'],
            'thank_you_sent' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = $this->normalizeGuestPayload($data);
        $wasEditing = (bool) $this->editingGuestId;

        if ($wasEditing) {
            $this->findGuest($this->editingGuestId)->update($payload);
        } else {
            $this->getRecord()->guests()->create($payload);
        }

        $this->getRecord()->unsetRelation('guests');
        $this->closeGuestEditor();

        Notification::make()
            ->title($wasEditing ? 'Guest updated' : 'Guest created')
            ->success()
            ->send();
    }

    public function openImportPanel(): void
    {
        $this->showImportPanel = true;
    }

    public function closeImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->guestImportFile = null;
        $this->importOptions = ['replace_existing' => false];
    }

    public function downloadGuestTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues([
                'Last Name',
                'First Name',
                'Title',
                'Suffix',
                'Street Address',
                'Address Line 2',
                'City',
                'State',
                'Zip',
                'Country',
                'Phone',
                'Email',
                'Group',
                'Guest List',
            ]));
            $writer->addRow(Row::fromValues(['Smith', 'John', 'Mr.', '', '2111 12th Avenue', '', 'San Diego', 'CA', '92006', 'USA', '760-933-0222', 'john@example.com', 'Family', 'A List']));
            $writer->addRow(Row::fromValues(['Smith', 'Jane', 'Mrs.', '', '', '', '', '', '', '', '', '', '', '']));
            $writer->addRow(Row::fromValues(['Smith', 'Leslie-Anne', '', '', '', '', '', '', '', '', '', '', '', '']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Doe', 'Richard', 'Mr.', '', '8776 Dutton Drive', '', 'San Clemente', 'CA', '92066', 'USA', '760-988-3332', 'richard@example.com', 'Friends', 'A List']));
            $writer->addRow(Row::fromValues(['Doe', 'Cindy', 'Mrs.', '', '', '', '', '', '', '', '', '', '', '']));
            $writer->close();
        }, 'guests-template-by-individual.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportRsvpResponses(): StreamedResponse
    {
        $project = $this->getRecord();
        $fields = $project->rsvpConfigurationFields();
        $filename = sprintf('%s-rsvp-responses.xlsx', str($project->name)->slug()->value() ?: 'rsvp');

        return response()->streamDownload(function () use ($project, $fields): void {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(array_merge([
                'RSVP #',
                'Guest names',
                'Completed at',
                'Ceremony',
                'Reception',
                'Email',
                'Phone',
                'Address',
            ], collect($fields)->pluck('label')->all())));

            foreach ($project->guests()->orderBy('rsvp_number')->get() as $guest) {
                $response = $guest->rsvp_response ?? [];
                $writer->addRow(Row::fromValues(array_merge([
                    $guest->rsvp_number,
                    $guest->displayName(),
                    $guest->rsvp_completed_at?->format('Y-m-d H:i'),
                    $this->formatStatus((int) $guest->ceremony),
                    $this->formatStatus((int) $guest->reception),
                    $guest->email,
                    $guest->phone,
                    collect([$guest->address_line_1, $guest->address_line_2, $guest->city, $guest->state, $guest->postal_code, $guest->country])->filter()->implode(', '),
                ], collect($fields)->map(function (array $field) use ($response): string {
                    $value = $response[$field['key']] ?? '';

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

                    return is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value;
                })->all())));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importGuests(): void
    {
        $data = validator(
            [
                'file' => $this->guestImportFile,
                'replace_existing' => $this->importOptions['replace_existing'] ?? false,
            ],
            [
                'file' => ['required', 'file', 'mimes:xlsx'],
                'replace_existing' => ['boolean'],
            ]
        )->validate();

        if ($data['replace_existing']) {
            $this->getRecord()->guests()->delete();
        }

        $created = 0;
        $parties = $this->readGuestPartiesFromSpreadsheet($this->guestImportFile->getRealPath());

        foreach ($parties as $party) {
            $this->getRecord()->guests()->create($this->normalizeImportedParty($party));
            $created++;
        }

        $this->getRecord()->unsetRelation('guests');
        $this->closeImportPanel();

        Notification::make()
            ->title(sprintf('%d guest parties imported', $created))
            ->success()
            ->send();
    }

    public function promptDeleteGuest(int $guestId): void
    {
        $this->confirmDeleteGuestId = $this->findGuest($guestId)->id;
    }

    public function cancelDeleteGuest(): void
    {
        $this->confirmDeleteGuestId = null;
    }

    public function confirmDeleteGuest(): void
    {
        if (! $this->confirmDeleteGuestId) {
            return;
        }

        $this->findGuest($this->confirmDeleteGuestId)->delete();

        $this->confirmDeleteGuestId = null;
        $this->getRecord()->unsetRelation('guests');

        Notification::make()
            ->title('Guest deleted')
            ->success()
            ->send();
    }

    public function toggleGuestStatus(int $guestId, string $field): void
    {
        if (! in_array($field, self::STATUS_FIELDS, true)) {
            return;
        }

        $guest = $this->findGuest($guestId);
        $currentValue = (int) $guest->{$field};
        $nextValue = match ($currentValue) {
            0 => 1,
            1 => -1,
            default => 0,
        };

        $guest->forceFill([$field => $nextValue])->save();
        $this->getRecord()->unsetRelation('guests');
    }

    protected function findGuest(int $guestId): Guest
    {
        /** @var Guest $guest */
        $guest = $this->getRecord()->guests()->findOrFail($guestId);

        return $guest;
    }

    protected function resetGuestForm(): void
    {
        $this->guestForm = [
            'rsvp_number' => null,
            'guest_list' => 'A List',
            'group_name' => '',
            'primary_title' => '',
            'primary_first_name' => '',
            'primary_last_name' => '',
            'primary_suffix' => '',
            'primary_role' => '',
            'primary_gender' => '',
            'partner_title' => '',
            'partner_first_name' => '',
            'partner_last_name' => '',
            'partner_suffix' => '',
            'partner_role' => '',
            'partner_gender' => '',
            'unspecified_plus_one' => false,
            'additional_guests' => [],
            'formal_addressing' => '',
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'invite_sent' => false,
            'ceremony' => false,
            'reception' => false,
            'out_of_town' => false,
            'gift_received' => false,
            'thank_you_sent' => false,
            'notes' => '',
        ];
    }

    protected function normalizeGuestPayload(array $data): array
    {
        $additionalGuests = collect($data['additional_guests'] ?? [])
            ->map(fn (array $guest): array => [
                'first_name' => trim((string) ($guest['first_name'] ?? '')),
                'last_name' => trim((string) ($guest['last_name'] ?? '')),
                'role' => trim((string) ($guest['role'] ?? '')),
                'type' => trim((string) ($guest['type'] ?? '')),
                'age' => ($guest['age'] ?? '') !== '' ? (string) $guest['age'] : '',
                'gender' => trim((string) ($guest['gender'] ?? '')),
            ])
            ->filter(fn (array $guest): bool => collect($guest)->filter()->isNotEmpty())
            ->values()
            ->all();

        $formalAddressing = trim((string) ($data['formal_addressing'] ?? ''));

        return [
            'rsvp_number' => $data['rsvp_number'] ?: null,
            'guest_list' => $this->nullableString($data['guest_list'] ?? null),
            'group_name' => $this->nullableString($data['group_name'] ?? null),
            'primary_title' => $this->nullableString($data['primary_title'] ?? null),
            'primary_first_name' => trim((string) $data['primary_first_name']),
            'primary_last_name' => $this->nullableString($data['primary_last_name'] ?? null),
            'primary_suffix' => $this->nullableString($data['primary_suffix'] ?? null),
            'primary_role' => $this->nullableString($data['primary_role'] ?? null),
            'primary_gender' => $this->nullableString($data['primary_gender'] ?? null),
            'partner_title' => $this->nullableString($data['partner_title'] ?? null),
            'partner_first_name' => $this->nullableString($data['partner_first_name'] ?? null),
            'partner_last_name' => $this->nullableString($data['partner_last_name'] ?? null),
            'partner_suffix' => $this->nullableString($data['partner_suffix'] ?? null),
            'partner_role' => $this->nullableString($data['partner_role'] ?? null),
            'partner_gender' => $this->nullableString($data['partner_gender'] ?? null),
            'unspecified_plus_one' => (bool) ($data['unspecified_plus_one'] ?? false),
            'additional_guests' => $additionalGuests,
            'formal_addressing' => $formalAddressing !== '' ? $formalAddressing : $this->buildFormalAddressing($data),
            'address_line_1' => $this->nullableString($data['address_line_1'] ?? null),
            'address_line_2' => $this->nullableString($data['address_line_2'] ?? null),
            'city' => $this->nullableString($data['city'] ?? null),
            'state' => $this->nullableString($data['state'] ?? null),
            'postal_code' => $this->nullableString($data['postal_code'] ?? null),
            'country' => $this->nullableString($data['country'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'invite_sent' => (bool) ($data['invite_sent'] ?? false) ? 1 : 0,
            'ceremony' => (bool) ($data['ceremony'] ?? false) ? 1 : 0,
            'reception' => (bool) ($data['reception'] ?? false) ? 1 : 0,
            'out_of_town' => (bool) ($data['out_of_town'] ?? false) ? 1 : 0,
            'gift_received' => (bool) ($data['gift_received'] ?? false) ? 1 : 0,
            'thank_you_sent' => (bool) ($data['thank_you_sent'] ?? false) ? 1 : 0,
            'notes' => $this->nullableString($data['notes'] ?? null),
        ];
    }

    protected function readGuestPartiesFromSpreadsheet(string $path): array
    {
        $options = new XlsxReaderOptions();
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;

        $reader = new Reader($options);
        $reader->open($path);
        $parties = [];
        $currentParty = [];
        $headerChecked = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $values = array_map(fn ($value): string => trim((string) $value), $row->toArray());

                if (! $headerChecked) {
                    $headerChecked = true;

                    if ($this->looksLikeImportHeader($values)) {
                        continue;
                    }
                }

                if ($this->looksLikeImportHeader($values)) {
                    continue;
                }

                if ($this->isBlankSpreadsheetRow($values)) {
                    if ($currentParty !== []) {
                        $parties[] = $currentParty;
                        $currentParty = [];
                    }

                    continue;
                }

                $currentParty[] = $values;
            }

            break;
        }

        if ($currentParty !== []) {
            $parties[] = $currentParty;
        }

        $reader->close();

        return array_values(array_filter($parties));
    }

    protected function normalizeImportedParty(array $rows): array
    {
        $primary = $this->spreadsheetRowToGuestPerson($rows[0] ?? []);
        $second = $this->spreadsheetRowToGuestPerson($rows[1] ?? []);
        $hasPartner = filled($second['first_name'] ?? null);

        $additionalRows = array_slice($rows, $hasPartner ? 2 : 1);
        $additionalGuests = collect($additionalRows)
            ->map(fn (array $row): array => $this->spreadsheetRowToGuestPerson($row))
            ->filter(fn (array $person): bool => filled($person['first_name'] ?? null))
            ->map(fn (array $person): array => [
                'first_name' => $person['first_name'],
                'last_name' => $person['last_name'],
                'role' => $person['role'] ?: $person['title'],
                'type' => 'Guest',
                'gender' => '',
            ])
            ->values()
            ->all();

        $payload = [
            'rsvp_number' => $this->nextRsvpNumber(),
            'guest_list' => $this->nullableString($primary['guest_list']) ?: 'A List',
            'group_name' => $this->nullableString($primary['group_name']),
            'primary_title' => $this->nullableString($primary['title']),
            'primary_first_name' => $primary['first_name'] ?: 'Guest',
            'primary_last_name' => $this->nullableString($primary['last_name']),
            'primary_suffix' => $this->nullableString($primary['suffix']),
            'primary_role' => null,
            'primary_gender' => null,
            'partner_title' => $hasPartner ? $this->nullableString($second['title']) : null,
            'partner_first_name' => $hasPartner ? $this->nullableString($second['first_name']) : null,
            'partner_last_name' => $hasPartner ? $this->nullableString($second['last_name']) : null,
            'partner_suffix' => $hasPartner ? $this->nullableString($second['suffix']) : null,
            'partner_role' => null,
            'partner_gender' => null,
            'unspecified_plus_one' => false,
            'additional_guests' => $additionalGuests,
            'formal_addressing' => null,
            'address_line_1' => $this->nullableString($primary['address_line_1']),
            'address_line_2' => $this->nullableString($primary['address_line_2']),
            'city' => $this->nullableString($primary['city']),
            'state' => $this->nullableString($primary['state']),
            'postal_code' => $this->nullableString($primary['postal_code']),
            'country' => $this->nullableString($primary['country']),
            'phone' => $this->nullableString($primary['phone']),
            'email' => $this->nullableString($primary['email']),
            'invite_sent' => 0,
            'ceremony' => 0,
            'reception' => 0,
            'out_of_town' => 0,
            'gift_received' => 0,
            'thank_you_sent' => 0,
            'notes' => null,
        ];

        $payload['formal_addressing'] = $this->buildFormalAddressing($payload);

        return $payload;
    }

    protected function spreadsheetRowToGuestPerson(array $row): array
    {
        return [
            'last_name' => $row[0] ?? '',
            'first_name' => $row[1] ?? '',
            'title' => $row[2] ?? '',
            'suffix' => $row[3] ?? '',
            'address_line_1' => $row[4] ?? '',
            'address_line_2' => $row[5] ?? '',
            'city' => $row[6] ?? '',
            'state' => $row[7] ?? '',
            'postal_code' => $row[8] ?? '',
            'country' => $row[9] ?? '',
            'phone' => $row[10] ?? '',
            'email' => $row[11] ?? '',
            'group_name' => $row[12] ?? '',
            'guest_list' => $row[13] ?? '',
            'role' => '',
        ];
    }

    protected function looksLikeImportHeader(array $values): bool
    {
        return strcasecmp($values[0] ?? '', 'Last Name') === 0
            && strcasecmp($values[1] ?? '', 'First Name') === 0;
    }

    protected function isBlankSpreadsheetRow(array $values): bool
    {
        return collect($values)->filter(fn (string $value): bool => $value !== '')->isEmpty();
    }

    protected function buildFormalAddressing(array $data): ?string
    {
        $primary = trim(collect([
            $data['primary_title'] ?? null,
            $data['primary_first_name'] ?? null,
            $data['primary_last_name'] ?? null,
        ])->filter()->implode(' '));

        $partner = trim(collect([
            $data['partner_title'] ?? null,
            $data['partner_first_name'] ?? null,
            $data['partner_last_name'] ?? null,
        ])->filter()->implode(' '));

        return collect([$primary, $partner])->filter()->implode(' & ') ?: null;
    }

    protected function nextRsvpNumber(): int
    {
        return ((int) $this->getRecord()->guests()->max('rsvp_number')) + 1;
    }

    protected function formatStatus(int $status): string
    {
        return match ($status) {
            1 => 'Yes',
            -1 => 'No',
            default => '',
        };
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
