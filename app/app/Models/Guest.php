<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'project_id',
        'rsvp_token',
        'rsvp_number',
        'guest_list',
        'group_name',
        'primary_title',
        'primary_first_name',
        'primary_last_name',
        'primary_suffix',
        'primary_role',
        'primary_gender',
        'partner_title',
        'partner_first_name',
        'partner_last_name',
        'partner_suffix',
        'partner_role',
        'partner_gender',
        'unspecified_plus_one',
        'additional_guests',
        'formal_addressing',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'invite_sent',
        'ceremony',
        'reception',
        'out_of_town',
        'gift_received',
        'thank_you_sent',
        'rsvp_response',
        'presence_confirmed',
        'rsvp_completed_at',
        'notes',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'rsvp_number' => 'integer',
        'unspecified_plus_one' => 'boolean',
        'additional_guests' => 'array',
        'invite_sent' => 'integer',
        'ceremony' => 'integer',
        'reception' => 'integer',
        'out_of_town' => 'integer',
        'gift_received' => 'integer',
        'thank_you_sent' => 'integer',
        'rsvp_response' => 'array',
        'presence_confirmed' => 'boolean',
        'rsvp_completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Guest $guest): void {
            if (blank($guest->rsvp_token)) {
                $guest->rsvp_token = Str::random(40);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function displayName(): string
    {
        $primary = collect([$this->primary_first_name, $this->primary_last_name])->filter()->implode(' ');
        $partner = collect([$this->partner_first_name, $this->partner_last_name])->filter()->implode(' ');

        return collect([$primary, $partner])->filter()->implode(' & ') ?: 'Unnamed guest';
    }

    public function additionalGuestNames(): string
    {
        return collect($this->additional_guests ?? [])
            ->map(fn (array $guest): string => trim(collect([$guest['first_name'] ?? null, $guest['last_name'] ?? null])->filter()->implode(' ')))
            ->filter()
            ->implode(', ');
    }

    public function partySize(): int
    {
        return 1
            + (filled($this->partner_first_name) || $this->unspecified_plus_one ? 1 : 0)
            + collect($this->additional_guests ?? [])->count();
    }

    public function normalizedAdditionalGuests(): Collection
    {
        return collect($this->additional_guests ?? [])->map(fn (array $guest): array => [
            'first_name' => trim((string) ($guest['first_name'] ?? '')),
            'last_name' => trim((string) ($guest['last_name'] ?? '')),
            'role' => trim((string) ($guest['role'] ?? '')),
            'type' => trim((string) ($guest['type'] ?? '')),
            'age' => ($guest['age'] ?? '') !== '' ? (string) $guest['age'] : '',
            'gender' => trim((string) ($guest['gender'] ?? '')),
            'high_chair' => (bool) ($guest['high_chair'] ?? false),
        ]);
    }

    public function publicRsvpUrl(): string
    {
        return route('public.rsvp.show', ['token' => $this->rsvp_token]);
    }
}
