<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGuestRsvpController extends Controller
{
    public function show(string $token): View
    {
        $guest = $this->findGuest($token);

        return view('public.guest-rsvp', [
            'guest' => $guest,
            'project' => $guest->project,
            'fields' => $guest->project->rsvpConfigurationFields(),
            'response' => $guest->rsvp_response ?? [],
            'subjects' => $this->guestResponseSubjects($guest),
            'rsvpLocked' => (bool) $guest->project->rsvp_submissions_locked,
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $guest = $this->findGuest($token);

        if ($guest->project->rsvp_submissions_locked) {
            return redirect()
                ->route('public.rsvp.show', ['token' => $guest->rsvp_token])
                ->with('status', 'RSVP changes are currently closed. Please contact your wedding planner for updates.');
        }

        $fields = collect($guest->project->rsvpConfigurationFields());
        $presenceData = $request->validate([
            'presence_confirmed' => ['required', 'boolean'],
        ]);
        $presenceConfirmed = (bool) $presenceData['presence_confirmed'];

        if (! $presenceConfirmed) {
            $guest->forceFill([
                'presence_confirmed' => false,
                'rsvp_response' => [],
                'rsvp_completed_at' => now(),
            ])->save();

            return redirect()
                ->route('public.rsvp.show', ['token' => $guest->rsvp_token])
                ->with('status', 'RSVP saved. Thank you.');
        }

        $rules = [
            'presence_confirmed' => ['required', 'boolean'],
            'primary_first_name' => ['required', 'string', 'max:255'],
            'primary_last_name' => ['required', 'string', 'max:255'],
            'partner_first_name' => ['nullable', 'string', 'max:255'],
            'partner_last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'additional_guests' => ['array'],
            'additional_guests.*.first_name' => ['nullable', 'string', 'max:255'],
            'additional_guests.*.last_name' => ['nullable', 'string', 'max:255'],
            'additional_guests.*.role' => ['nullable', 'string', 'max:255'],
            'additional_guests.*.type' => ['nullable', 'string', 'max:50'],
            'additional_guests.*.age' => ['nullable', 'integer', 'min:0', 'max:18'],
            'additional_guests.*.high_chair' => ['nullable', 'boolean'],
            'rsvp_response' => ['array'],
        ];

        foreach ($fields as $field) {
            if (! ($field['enabled'] ?? false)) {
                continue;
            }

            if (($field['response_scope'] ?? 'aggregate') === 'per_guest') {
                $rules['rsvp_response.' . $field['key']] = ['array'];
                $rules['rsvp_response.' . $field['key'] . '.*'] = match ($field['type'] ?? 'text') {
                    'checkbox' => ['nullable', 'boolean'],
                    'select' => ['nullable', 'string', 'max:255'],
                    default => ['nullable', 'string', 'max:2000'],
                };

                continue;
            }

            $rules['rsvp_response.' . $field['key']] = match ($field['type'] ?? 'text') {
                'checkbox' => ['nullable', 'boolean'],
                'select' => ['nullable', 'string', 'max:255'],
                default => ['nullable', 'string', 'max:2000'],
            };
        }

        $data = $request->validate($rules);
        $responses = [];

        foreach ($fields as $field) {
            if (! ($field['enabled'] ?? false)) {
                continue;
            }

            $key = $field['key'];
            if (($field['response_scope'] ?? 'aggregate') === 'per_guest') {
                $submittedValues = $data['rsvp_response'][$key] ?? [];
                $responses[$key] = collect($this->guestResponseSubjects($guest, $data['additional_guests'] ?? []))
                    ->mapWithKeys(function (array $subject) use ($submittedValues, $field): array {
                        $value = $submittedValues[$subject['key']] ?? null;

                        return [
                            $subject['key'] => [
                                'label' => $subject['label'],
                                'value' => ($field['type'] ?? 'text') === 'checkbox'
                                    ? (bool) $value
                                    : trim((string) $value),
                            ],
                        ];
                    })
                    ->all();

                continue;
            }

            $responses[$key] = ($field['type'] ?? 'text') === 'checkbox'
                ? (bool) ($data['rsvp_response'][$key] ?? false)
                : trim((string) ($data['rsvp_response'][$key] ?? ''));
        }

        $guest->forceFill([
            'primary_first_name' => trim((string) $data['primary_first_name']),
            'primary_last_name' => $this->nullableString($data['primary_last_name'] ?? null),
            'partner_first_name' => $this->nullableString($data['partner_first_name'] ?? null),
            'partner_last_name' => $this->nullableString($data['partner_last_name'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'address_line_1' => $this->nullableString($data['address_line_1'] ?? null),
            'address_line_2' => $this->nullableString($data['address_line_2'] ?? null),
            'city' => $this->nullableString($data['city'] ?? null),
            'state' => $this->nullableString($data['state'] ?? null),
            'postal_code' => $this->nullableString($data['postal_code'] ?? null),
            'country' => $this->nullableString($data['country'] ?? null),
            'additional_guests' => $this->normalizeAdditionalGuests($data['additional_guests'] ?? []),
            'rsvp_response' => $responses,
            'presence_confirmed' => true,
            'rsvp_completed_at' => now(),
        ])->save();

        return redirect()
            ->route('public.rsvp.show', ['token' => $guest->rsvp_token])
            ->with('status', 'RSVP saved. Thank you.');
    }

    protected function findGuest(string $token): Guest
    {
        return Guest::query()
            ->with('project')
            ->where('rsvp_token', $token)
            ->firstOrFail();
    }

    protected function normalizeAdditionalGuests(array $guests): array
    {
        return collect($guests)
            ->map(fn (array $guest): array => [
                'first_name' => trim((string) ($guest['first_name'] ?? '')),
                'last_name' => trim((string) ($guest['last_name'] ?? '')),
                'role' => trim((string) ($guest['role'] ?? '')),
                'type' => trim((string) ($guest['type'] ?? '')),
                'age' => ($guest['type'] ?? null) === 'Child' && ($guest['age'] ?? '') !== ''
                    ? (int) $guest['age']
                    : '',
                'high_chair' => ($guest['type'] ?? null) === 'Child'
                    && ($guest['age'] ?? '') !== ''
                    && (int) $guest['age'] <= 3
                    && (bool) ($guest['high_chair'] ?? false),
                'gender' => trim((string) ($guest['gender'] ?? '')),
            ])
            ->filter(fn (array $guest): bool => collect($guest)->filter()->isNotEmpty())
            ->values()
            ->all();
    }

    protected function guestResponseSubjects(Guest $guest, array $submittedAdditionalGuests = []): array
    {
        $additionalGuests = $submittedAdditionalGuests ?: ($guest->additional_guests ?? []);

        return collect([
            [
                'key' => 'primary',
                'type' => 'primary',
                'index' => null,
                'label' => trim(collect([$guest->primary_first_name, $guest->primary_last_name])->filter()->implode(' ')) ?: 'Primary guest',
            ],
            [
                'key' => 'partner',
                'type' => 'partner',
                'index' => null,
                'label' => trim(collect([$guest->partner_first_name, $guest->partner_last_name])->filter()->implode(' ')) ?: 'Partner / Plus-one',
            ],
        ])
            ->concat(collect($additionalGuests)->values()->map(fn (array $additionalGuest, int $index): array => [
                'key' => 'additional_' . $index,
                'type' => 'additional',
                'index' => $index,
                'label' => trim(collect([$additionalGuest['first_name'] ?? null, $additionalGuest['last_name'] ?? null])->filter()->implode(' ')) ?: 'Additional guest ' . ($index + 1),
            ]))
            ->values()
            ->all();
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
