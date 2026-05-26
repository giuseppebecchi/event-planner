<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $guests = $this->getGuests();
        $summary = $this->getGuestSummary();
        $statusColumns = [
            'invite_sent' => 'Invite Sent',
            'ceremony' => 'Ceremony',
            'reception' => 'Reception',
            'out_of_town' => 'Out of Town',
            'gift_received' => 'Gift',
            'thank_you_sent' => 'Thank You',
        ];
        $isCustomer = auth()->user()?->isCustomer();
    @endphp

    <style>
        .wm-guests-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wm-guests-shell {
            width: min(1440px, calc(100% - 2rem));
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        [x-cloak] {
            display: none !important;
        }

        .wm-event-card {
            border: 1px solid var(--cup-border-soft, #e8e3dc);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 20px 42px rgba(45, 42, 38, 0.06);
        }

        .wm-event-top {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            align-items: start;
            padding: 0.9rem 1rem 1rem;
        }

        .wm-event-top-head {
            width: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.9rem 1rem;
            align-items: center;
        }

        .wm-event-top-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.2rem, 1.8vw, 1.65rem);
            line-height: 1.08;
            color: #2d2a26;
        }

        .wm-event-top-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem 0.95rem;
            margin-top: 0.4rem;
            color: #746d66;
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .wm-event-top-meta span:not(:last-child)::after {
            content: "•";
            margin-left: 0.95rem;
            color: #c9a96a;
        }

        .wm-event-top-side {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .wm-event-summary-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 6rem;
            padding: 0.62rem 0.78rem;
            border-radius: 1rem;
            border: 1px solid rgba(201, 169, 106, 0.22);
            background: rgba(255, 255, 255, 0.85);
            color: #5f5953;
        }

        .wm-event-summary-chip-label {
            margin: 0;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #9a8f82;
        }

        .wm-event-summary-chip-value {
            margin: 0.16rem 0 0;
            font-size: 0.98rem;
            font-weight: 700;
            color: #2d2a26;
        }

        .wm-event-countdown {
            min-width: 11.5rem;
            padding: 0.62rem 0.82rem;
            border-radius: 1rem;
            background: linear-gradient(160deg, rgba(46, 74, 98, 0.96), rgba(36, 60, 81, 0.98));
            color: #f7f3ed;
        }

        .wm-event-countdown-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .wm-event-countdown-label {
            margin: 0;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.64);
        }

        .wm-event-countdown-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            color: rgba(255, 255, 255, 0.86);
            cursor: pointer;
        }

        .wm-event-countdown-edit svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-event-countdown-value {
            margin: 0.18rem 0 0;
            color: #fff;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .wm-event-countdown-meta {
            margin: 0.1rem 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.8rem;
        }

        .wm-event-workspace {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            overflow-x: auto;
            width: 100%;
            padding: 0.28rem;
            border-radius: 1.2rem;
            background: rgba(247, 243, 237, 0.96);
            scrollbar-width: none;
            border: 1px solid #ece5dd;
        }

        .wm-event-workspace::-webkit-scrollbar {
            display: none;
        }

        .wm-event-workspace-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            min-height: 2.45rem;
            padding: 0 0.88rem;
            border-radius: 999px;
            color: #746d66;
            font-size: 0.69rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: nowrap;
            text-decoration: none;
        }

        .wm-event-workspace-link.is-active {
            background: rgba(122, 143, 123, 0.14);
            color: #2d7a39;
        }

        .wm-event-workspace-link.is-disabled {
            opacity: 0.45;
        }

        .wm-event-top-date-tools {
            width: 100%;
        }

        .wm-event-date-editor {
            display: grid;
            gap: 0.85rem;
            width: 100%;
            max-width: 38rem;
            padding: 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-event-date-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #4d473f;
            font-weight: 600;
        }

        .wm-event-date-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-event-date-grid.is-single {
            grid-template-columns: minmax(0, 1fr);
            max-width: 16rem;
        }

        .wm-event-date-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-event-date-input {
            width: 100%;
            min-height: 2.9rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0 0.95rem;
            color: #2d2a26;
        }

        .wm-event-date-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .wm-guests-toolbar {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
        }

        .wm-guests-actions,
        .wm-guests-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            align-items: center;
        }

        .wm-guests-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.8rem;
            padding: 0 1rem;
            border: 1px solid #b9975b;
            border-radius: 0.45rem;
            background: #b9975b;
            color: #fff;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-guests-button.is-secondary {
            background: #fffdfa;
            color: #7a5e28;
            border-color: #dfd0bf;
        }

        .wm-guests-button svg,
        .wm-guests-icon-button svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-guests-stat {
            min-width: 7.8rem;
            padding: 0.6rem 0.78rem;
            border: 1px solid rgba(201, 169, 106, 0.22);
            border-radius: 0.8rem;
            background: rgba(255, 255, 255, 0.85);
        }

        .wm-guests-stat-label {
            margin: 0;
            color: #9a8f82;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-guests-stat-value {
            margin: 0.1rem 0 0;
            color: #2d2a26;
            font-size: 1.02rem;
            font-weight: 800;
        }

        .wm-guests-table-card {
            overflow: hidden;
        }

        .wm-guests-table-scroll {
            overflow-x: auto;
        }

        .wm-guests-table {
            width: 100%;
            min-width: 1320px;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .wm-guests-table th {
            padding: 0.92rem 0.8rem;
            background: #f7f4ef;
            color: #4f4943;
            text-align: left;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .wm-guests-table td {
            padding: 0.88rem 0.8rem;
            border-top: 1px solid #eee7de;
            color: #514b45;
            vertical-align: middle;
        }

        .wm-guests-table tbody tr:hover {
            background: #fffaf2;
        }

        .wm-guests-name {
            color: #2d2a26;
            font-size: 1rem;
            font-weight: 700;
        }

        .wm-guests-muted {
            color: #968c82;
            font-size: 0.82rem;
        }

        .wm-guests-chip {
            display: inline-flex;
            align-items: center;
            min-height: 1.75rem;
            padding: 0 0.65rem;
            border-radius: 999px;
            background: #f4eee6;
            color: #61584d;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .wm-guests-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #446545;
        }

        .wm-guests-status-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            border: 1px solid transparent;
            border-radius: 999px;
            background: transparent;
            color: transparent;
            cursor: pointer;
        }

        .wm-guests-status-button svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        .wm-guests-status-button.is-positive {
            background: rgba(68, 101, 69, 0.1);
            border-color: rgba(68, 101, 69, 0.18);
            color: #2f7a3a;
        }

        .wm-guests-status-button.is-negative {
            background: rgba(17, 17, 17, 0.06);
            border-color: rgba(17, 17, 17, 0.16);
            color: #111;
        }

        .wm-guests-empty {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #857d76;
        }

        .wm-guests-row-actions {
            display: inline-flex;
            gap: 0.4rem;
        }

        .wm-guests-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: #f4eee6;
            color: #6a6158;
            cursor: pointer;
        }

        .wm-guests-icon-button.is-danger {
            color: #a96f66;
        }

        .wm-guests-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(31, 25, 20, 0.34);
        }

        .wm-guests-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 50;
            width: min(58rem, calc(100vw - 2rem));
            max-height: calc(100vh - 2rem);
            overflow: auto;
            transform: translate(-50%, -50%);
            border: 1px solid #d9ccc0;
            border-radius: 1.1rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 60px rgba(24, 18, 14, 0.18);
            padding: 1.35rem;
        }

        .wm-guests-modal.is-compact {
            width: min(32rem, calc(100vw - 2rem));
        }

        .wm-guests-modal-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: start;
            margin-bottom: 1rem;
        }

        .wm-guests-modal-title {
            margin: 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            line-height: 1.2;
        }

        .wm-guests-section {
            display: grid;
            gap: 0.8rem;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 1px solid #eadfce;
        }

        .wm-guests-section:first-of-type {
            padding-top: 0;
            margin-top: 0;
            border-top: 0;
        }

        .wm-guests-section-title {
            margin: 0;
            color: #111;
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .wm-guests-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-guests-grid.is-two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .wm-guests-field label,
        .wm-guests-check-field span {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-guests-input,
        .wm-guests-textarea,
        .wm-guests-select {
            width: 100%;
            min-height: 2.75rem;
            border: 1px solid #ddd2c5;
            border-radius: 0.45rem;
            background: #fff;
            padding: 0.68rem 0.78rem;
            color: #2d2a26;
        }

        .wm-guests-textarea {
            min-height: 5.5rem;
            resize: vertical;
        }

        .wm-guests-checks {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.6rem;
        }

        .wm-guests-check-field {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
            min-height: 2.75rem;
            border: 1px solid #ddd2c5;
            border-radius: 0.45rem;
            background: #fff;
            padding: 0 0.78rem;
            cursor: pointer;
        }

        .wm-guests-check-field span {
            margin-bottom: 0;
        }

        .wm-guests-check-field input {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: #b9975b;
        }

        .wm-guests-additional-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr)) 2.2rem;
            gap: 0.65rem;
            align-items: center;
        }

        .wm-guests-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.7rem;
            margin-top: 1.1rem;
        }

        @media (max-width: 1000px) {
            .wm-guests-shell {
                width: min(100%, calc(100% - 1rem));
            }

            .wm-event-top-head {
                grid-template-columns: minmax(0, 1fr);
            }

            .wm-event-top-side {
                flex-direction: column;
                align-items: stretch;
            }

            .wm-event-summary-chip,
            .wm-event-countdown {
                width: 100%;
            }

            .wm-guests-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .wm-guests-grid,
            .wm-guests-grid.is-two,
            .wm-guests-checks,
            .wm-guests-additional-row {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="wm-guests-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'guests',
        ])

        <div class="wm-guests-shell">
            <section class="wm-event-card wm-guests-toolbar">
                <div class="wm-guests-actions">
                    <button type="button" class="wm-guests-button" wire:click="startCreateGuest">
                        <x-heroicon-o-user-plus />
                        <span>Add guests</span>
                    </button>
                    <button type="button" class="wm-guests-button is-secondary" wire:click="openImportPanel">
                        <x-heroicon-o-arrow-up-tray />
                        <span>Import guest list</span>
                    </button>
                    @if (! $isCustomer)
                        <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('guests-rsvp-configuration', ['record' => $record]) }}" class="wm-guests-button is-secondary">
                            <x-heroicon-o-adjustments-horizontal />
                            <span>RSVP form</span>
                        </a>
                    @endif
                    <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('guests-rsvp-responses', ['record' => $record]) }}" class="wm-guests-button is-secondary">
                        <x-heroicon-o-table-cells />
                        <span>RSVP responses</span>
                    </a>
                </div>

                <div class="wm-guests-stats">
                    <div class="wm-guests-stat">
                        <p class="wm-guests-stat-label">Parties</p>
                        <p class="wm-guests-stat-value">{{ $summary['parties'] }}</p>
                    </div>
                    <div class="wm-guests-stat">
                        <p class="wm-guests-stat-label">People</p>
                        <p class="wm-guests-stat-value">{{ $summary['people'] }}</p>
                    </div>
                    <div class="wm-guests-stat">
                        <p class="wm-guests-stat-label">Invites</p>
                        <p class="wm-guests-stat-value">{{ $summary['invited'] }}</p>
                    </div>
                    <div class="wm-guests-stat">
                        <p class="wm-guests-stat-label">Groups</p>
                        <p class="wm-guests-stat-value">{{ $summary['groups'] }}</p>
                    </div>
                </div>
            </section>

            <section class="wm-event-card wm-guests-table-card">
                @if ($guests->isEmpty())
                    <div class="wm-guests-empty">
                        No guests yet. Add a guest party manually or import the Excel template.
                    </div>
                @else
                    <div class="wm-guests-table-scroll">
                        <table class="wm-guests-table">
                            <thead>
                                <tr>
                                    <th>RSVP #</th>
                                    <th>Names</th>
                                    <th>Formal Addressing</th>
                                    <th>Children/Add'l Guests</th>
                                    <th>List</th>
                                    @foreach ($statusColumns as $statusLabel)
                                        <th>{{ $statusLabel }}</th>
                                    @endforeach
                                    <th>Group</th>
                                    <th>RSVP</th>
                                    <th>Public Link</th>
                                    <th>Address</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guests as $guest)
                                    <tr wire:key="guest-row-{{ $guest->id }}">
                                        <td>{{ $guest->rsvp_number ?: '—' }}</td>
                                        <td>
                                            <div class="wm-guests-name">{{ $guest->displayName() }}</div>
                                            <div class="wm-guests-muted">{{ $guest->partySize() }} {{ $guest->partySize() === 1 ? 'person' : 'people' }}</div>
                                        </td>
                                        <td>{{ $guest->formal_addressing ?: '—' }}</td>
                                        <td>{{ $guest->additionalGuestNames() ?: '—' }}</td>
                                        <td><span class="wm-guests-chip">{{ $guest->guest_list ?: 'List' }}</span></td>
                                        @foreach ($statusColumns as $statusField => $statusLabel)
                                            @php $statusValue = (int) $guest->{$statusField}; @endphp
                                            <td>
                                                <button
                                                    type="button"
                                                    class="wm-guests-status-button {{ $statusValue === 1 ? 'is-positive' : ($statusValue === -1 ? 'is-negative' : '') }}"
                                                    wire:click="toggleGuestStatus({{ $guest->id }}, '{{ $statusField }}')"
                                                    title="{{ $statusLabel }}"
                                                >
                                                    @if ($statusValue === 1)
                                                        <x-heroicon-o-check />
                                                    @elseif ($statusValue === -1)
                                                        <x-heroicon-o-x-mark />
                                                    @else
                                                        <span>&nbsp;</span>
                                                    @endif
                                                </button>
                                            </td>
                                        @endforeach
                                        <td>{{ $guest->group_name ?: '—' }}</td>
                                        <td>
                                            @if ($guest->rsvp_completed_at)
                                                <span class="wm-guests-chip">Completed {{ $guest->rsvp_completed_at->format('d/m H:i') }}</span>
                                            @else
                                                <span class="wm-guests-muted">Not completed</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="wm-guests-chip" href="{{ $guest->publicRsvpUrl() }}" target="_blank" rel="noopener">Open RSVP</a>
                                        </td>
                                        <td>
                                            {{ collect([$guest->address_line_1, $guest->city, $guest->state, $guest->postal_code, $guest->country])->filter()->implode(', ') ?: '—' }}
                                        </td>
                                        <td>{{ $guest->phone ?: '—' }}</td>
                                        <td>{{ $guest->email ?: '—' }}</td>
                                        <td>
                                            <div class="wm-guests-row-actions">
                                                <button type="button" class="wm-guests-icon-button" wire:click="editGuest({{ $guest->id }})" title="Edit guest">
                                                    <x-heroicon-o-pencil-square />
                                                </button>
                                                <button type="button" class="wm-guests-icon-button is-danger" wire:click="promptDeleteGuest({{ $guest->id }})" title="Delete guest">
                                                    <x-heroicon-o-trash />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        @if ($showGuestEditor)
            <div class="wm-guests-modal-backdrop" wire:click="closeGuestEditor"></div>
            <div class="wm-guests-modal" role="dialog" aria-modal="true" x-on:mousedown.stop x-on:click.stop>
                <div class="wm-guests-modal-head">
                    <h3 class="wm-guests-modal-title">
                        {{ $editingGuestId ? 'Edit guest party' : 'Add guest party' }}
                    </h3>
                    <button type="button" class="wm-guests-icon-button" wire:click="closeGuestEditor">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <div class="wm-guests-section">
                    <p class="wm-guests-section-title">Guest names</p>
                    <div class="wm-guests-grid">
                        <div class="wm-guests-field">
                            <label>Title</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.primary_title" placeholder="Mr.">
                        </div>
                        <div class="wm-guests-field">
                            <label>First name</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.primary_first_name">
                        </div>
                        <div class="wm-guests-field">
                            <label>Last name</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.primary_last_name">
                        </div>
                        <div class="wm-guests-field">
                            <label>Suffix</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.primary_suffix">
                        </div>
                    </div>

                    <div class="wm-guests-grid">
                        <div class="wm-guests-field">
                            <label>Partner title</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.partner_title" placeholder="Mrs.">
                        </div>
                        <div class="wm-guests-field">
                            <label>Partner first</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.partner_first_name">
                        </div>
                        <div class="wm-guests-field">
                            <label>Partner last</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.partner_last_name">
                        </div>
                        <div class="wm-guests-field">
                            <label>Partner suffix</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.partner_suffix">
                        </div>
                    </div>

                    <div class="wm-guests-checks">
                        <label class="wm-guests-check-field">
                            <span>Unspecified plus-one</span>
                            <input type="checkbox" wire:model="guestForm.unspecified_plus_one">
                        </label>
                        <div class="wm-guests-field">
                            <label>Primary role</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.primary_role" placeholder="Best Man">
                        </div>
                        <div class="wm-guests-field">
                            <label>Partner role</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.partner_role" placeholder="Role">
                        </div>
                    </div>

                    <p class="wm-guests-section-title">Additional guests</p>
                    @forelse (($guestForm['additional_guests'] ?? []) as $index => $additionalGuest)
                        <div class="wm-guests-additional-row" wire:key="additional-guest-{{ $index }}">
                            <input type="text" class="wm-guests-input" wire:model="guestForm.additional_guests.{{ $index }}.first_name" placeholder="First name">
                            <input type="text" class="wm-guests-input" wire:model="guestForm.additional_guests.{{ $index }}.last_name" placeholder="Last name">
                            <input type="text" class="wm-guests-input" wire:model="guestForm.additional_guests.{{ $index }}.role" placeholder="Role">
                            <select class="wm-guests-select" wire:model="guestForm.additional_guests.{{ $index }}.type">
                                <option value="">Type</option>
                                <option value="Adult">Adult</option>
                                <option value="Child">Child</option>
                                <option value="Guest">Guest</option>
                            </select>
                            <select class="wm-guests-select" wire:model="guestForm.additional_guests.{{ $index }}.gender">
                                <option value="">Gender</option>
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                            <button type="button" class="wm-guests-icon-button is-danger" wire:click="removeAdditionalGuest({{ $index }})">
                                <x-heroicon-o-trash />
                            </button>
                        </div>
                    @empty
                        <div class="wm-guests-muted">No additional guests in this party.</div>
                    @endforelse
                    <div>
                        <button type="button" class="wm-guests-button is-secondary" wire:click="addAdditionalGuest">
                            <x-heroicon-o-plus />
                            <span>Add another guest</span>
                        </button>
                    </div>
                </div>

                <div class="wm-guests-section">
                    <p class="wm-guests-section-title">Contact information</p>
                    <div class="wm-guests-grid is-two">
                        <div class="wm-guests-field">
                            <label>Address line 1</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.address_line_1">
                        </div>
                        <div class="wm-guests-field">
                            <label>Address line 2</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.address_line_2">
                        </div>
                    </div>
                    <div class="wm-guests-grid">
                        <div class="wm-guests-field">
                            <label>City</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.city">
                        </div>
                        <div class="wm-guests-field">
                            <label>State</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.state">
                        </div>
                        <div class="wm-guests-field">
                            <label>Zip</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.postal_code">
                        </div>
                        <div class="wm-guests-field">
                            <label>Country</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.country">
                        </div>
                    </div>
                    <div class="wm-guests-grid">
                        <div class="wm-guests-field">
                            <label>Phone</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.phone">
                        </div>
                        <div class="wm-guests-field">
                            <label>Email</label>
                            <input type="email" class="wm-guests-input" wire:model="guestForm.email">
                        </div>
                        <div class="wm-guests-field">
                            <label>RSVP #</label>
                            <input type="number" class="wm-guests-input" wire:model="guestForm.rsvp_number">
                        </div>
                        <div class="wm-guests-field">
                            <label>Guest list</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.guest_list" placeholder="A List">
                        </div>
                    </div>
                    <div class="wm-guests-grid is-two">
                        <div class="wm-guests-field">
                            <label>Group</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.group_name">
                        </div>
                        <div class="wm-guests-field">
                            <label>Formal addressing</label>
                            <input type="text" class="wm-guests-input" wire:model="guestForm.formal_addressing">
                        </div>
                    </div>
                    <div class="wm-guests-checks">
                        <label class="wm-guests-check-field"><span>Invite sent</span><input type="checkbox" wire:model="guestForm.invite_sent"></label>
                        <label class="wm-guests-check-field"><span>Ceremony</span><input type="checkbox" wire:model="guestForm.ceremony"></label>
                        <label class="wm-guests-check-field"><span>Reception</span><input type="checkbox" wire:model="guestForm.reception"></label>
                        <label class="wm-guests-check-field"><span>Out of town</span><input type="checkbox" wire:model="guestForm.out_of_town"></label>
                        <label class="wm-guests-check-field"><span>Gift received</span><input type="checkbox" wire:model="guestForm.gift_received"></label>
                        <label class="wm-guests-check-field"><span>Thank you sent</span><input type="checkbox" wire:model="guestForm.thank_you_sent"></label>
                    </div>
                    <div class="wm-guests-field">
                        <label>Notes</label>
                        <textarea class="wm-guests-textarea" wire:model="guestForm.notes"></textarea>
                    </div>
                </div>

                <div class="wm-guests-modal-actions">
                    <x-filament::button color="gray" wire:click="closeGuestEditor">Cancel</x-filament::button>
                    <x-filament::button wire:click="saveGuest">{{ $editingGuestId ? 'Update guest' : 'Create guest party' }}</x-filament::button>
                </div>
            </div>
        @endif

        @if ($showImportPanel)
            <div class="wm-guests-modal-backdrop" wire:click="closeImportPanel"></div>
            <div class="wm-guests-modal is-compact" role="dialog" aria-modal="true" x-on:mousedown.stop x-on:click.stop>
                <div class="wm-guests-modal-head">
                    <div>
                        <h3 class="wm-guests-modal-title">Import guest list</h3>
                        <p class="wm-guests-muted">Use the By Individual Excel template. Blank rows split guest parties.</p>
                    </div>
                    <button type="button" class="wm-guests-icon-button" wire:click="closeImportPanel">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <div class="wm-guests-section">
                    <button type="button" class="wm-guests-button is-secondary" wire:click="downloadGuestTemplate">
                        <x-heroicon-o-document-arrow-down />
                        <span>Download template</span>
                    </button>

                    <div class="wm-guests-field">
                        <label>Excel file</label>
                        <input type="file" class="wm-guests-input" wire:model="guestImportFile" accept=".xlsx">
                    </div>
                    <label class="wm-guests-check-field">
                        <span>Replace existing guests</span>
                        <input type="checkbox" wire:model="importOptions.replace_existing">
                    </label>
                </div>

                <div class="wm-guests-modal-actions">
                    <x-filament::button color="gray" wire:click="closeImportPanel">Cancel</x-filament::button>
                    <x-filament::button wire:click="importGuests">
                        Import guest list
                    </x-filament::button>
                </div>
            </div>
        @endif

        @if ($confirmDeleteGuestId)
            <div class="wm-guests-modal-backdrop" wire:click="cancelDeleteGuest"></div>
            <div class="wm-guests-modal is-compact" role="dialog" aria-modal="true" x-on:mousedown.stop x-on:click.stop>
                <p>Deleting this guest party will permanently remove it from the project guest list.</p>
                <div class="wm-guests-modal-actions">
                    <x-filament::button color="gray" wire:click="cancelDeleteGuest">Cancel</x-filament::button>
                    <x-filament::button color="danger" wire:click="confirmDeleteGuest">Delete guest</x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
