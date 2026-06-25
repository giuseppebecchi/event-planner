<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} RSVP</title>
    @include('partials.favicons')
    <style>
        body {
            margin: 0;
            font-family: Inter, system-ui, sans-serif;
            color: #2d2a26;
            background:
                linear-gradient(rgba(247, 243, 237, .82), rgba(247, 243, 237, .92)),
                url('{{ asset('images/bg.jpg') }}') center center / cover fixed;
        }
        .page { width: min(1040px, calc(100% - 2rem)); margin: 0 auto; padding: 2rem 0; }
        .hero, .card { background: rgba(255,255,255,.94); border: 1px solid #e8e0d6; border-radius: 14px; box-shadow: 0 20px 44px rgba(45,42,38,.07); }
        .hero { overflow: hidden; margin-bottom: 1rem; }
        .hero-image { min-height: 18rem; background: linear-gradient(180deg, rgba(24,18,14,.14), rgba(24,18,14,.54)), var(--cover-image, linear-gradient(135deg, #d8c4a1, #f3eadc)); background-size: cover; background-position: center; display: flex; align-items: end; }
        .hero-content { width: 100%; padding: 1.35rem; color: #fff; text-shadow: 0 2px 18px rgba(0,0,0,.32); }
        h1 { margin: 0; font-family: Georgia, serif; font-size: clamp(2rem, 5vw, 4rem); line-height: 1.04; }
        .meta { display: flex; flex-wrap: wrap; gap: .55rem .9rem; margin: .65rem 0 0; color: rgba(255,255,255,.9); }
        .meta span:not(:last-child)::after { content: "•"; margin-left: .9rem; opacity: .75; }
        .guest-line { margin: .7rem 0 0; font-size: 1.05rem; color: rgba(255,255,255,.96); }
        .card { padding: 1.4rem; }
        .section { padding-top: 1.1rem; margin-top: 1.1rem; border-top: 1px solid #eadfce; }
        .section:first-child { padding-top: 0; margin-top: 0; border-top: 0; }
        h2 { margin: 0 0 .85rem; font-size: .82rem; letter-spacing: .16em; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; }
        .people { display: grid; gap: 1rem; }
        .person-card { display: grid; gap: 1rem; padding: 1rem; border: 1px solid #eadfce; border-radius: 12px; background: #fffdf9; }
        .person-title { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 800; line-height: 1.25; }
        .person-subtitle { margin: .2rem 0 0; color: #8d847b; font-size: .82rem; line-height: 1.35; }
        .person-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .person-questions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; padding-top: .85rem; border-top: 1px solid #eadfce; }
        .additional-type-age { display: grid; grid-template-columns: minmax(0, 1fr); gap: .8rem; }
        .additional-type-age.is-child { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .additional-age-field { display: none; }
        .additional-type-age.is-child .additional-age-field { display: block; }
        .additional-type-age .high-chair-field { display: none; }
        .additional-type-age.needs-high-chair .high-chair-field { display: flex; }
        label span { display: block; margin-bottom: .35rem; color: #5e5852; font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .required-mark { display: inline; margin: 0 0 0 .2rem; color: #dc2626; }
        input, select, textarea { width: 100%; min-height: 2.8rem; box-sizing: border-box; border: 1px solid #ddd2c5; border-radius: 8px; background: #fff; padding: .7rem .85rem; color: #2d2a26; font: inherit; }
        input:disabled, select:disabled, textarea:disabled { background: #f5f1eb; color: #7d746b; cursor: not-allowed; }
        textarea { min-height: 4.25rem; resize: vertical; }
        .check { display: flex; align-items: center; gap: .65rem; min-height: 2.8rem; }
        .check input { width: 1.1rem; min-height: 1.1rem; accent-color: #b9975b; }
        .presence-options { display: flex; flex-wrap: wrap; gap: .7rem; }
        .presence-option { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.8rem; padding: 0 1rem; border: 1px solid #ddd2c5; border-radius: 999px; background: #fffdf9; font-weight: 800; }
        .presence-option input { width: 1.05rem; min-height: 1.05rem; accent-color: #b9975b; }
        .help { margin: .3rem 0 0; color: #8d847b; font-size: .82rem; line-height: 1.45; }
        .per-guest { grid-column: 1 / -1; display: grid; gap: .75rem; padding: .85rem; border: 1px solid #eadfce; border-radius: 10px; background: #fffdf9; }
        .per-guest-title { margin: 0; font-weight: 800; color: #2d2a26; }
        .per-guest-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 3rem; padding: 0 1.2rem; border: 1px solid #b9975b; border-radius: 8px; background: #b9975b; color: #fff; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; cursor: pointer; }
        .notice { margin-bottom: 1rem; padding: .9rem 1rem; border-radius: 10px; background: #eaf5ea; color: #2f6f39; }
        .notice.is-locked { background: #fff4df; color: #7a4f13; }
        @media (max-width: 760px) { .grid, .grid-4, .person-fields, .person-questions, .per-guest-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <main class="page">
        @php
            $coverUrl = $project->cover_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover_image_path) : null;
            $dateLabel = $project->event_start_date
                ? ($project->event_end_date && ! $project->event_end_date->isSameDay($project->event_start_date)
                    ? $project->event_start_date->format('F j') . ' - ' . $project->event_end_date->format('F j, Y')
                    : $project->event_start_date->format('F j, Y'))
                : 'Date to be defined';
            $locationLabel = collect([$project->locality, $project->region])->filter()->implode(', ');
            $partnerLabel = $project->coupleNames();
        @endphp
        <section class="hero" style="{{ $coverUrl ? '--cover-image: url(' . $coverUrl . ')' : '' }}">
            <div class="hero-image">
                <div class="hero-content">
                    <h1>{{ $project->name }}</h1>
                    <div class="meta">
                        <span>{{ $dateLabel }}</span>
                        @if ($locationLabel)
                            <span>{{ $locationLabel }}</span>
                        @endif
                        @if ($partnerLabel)
                            <span>{{ $partnerLabel }}</span>
                        @endif
                    </div>
                    <p class="guest-line">RSVP for {{ $guest->displayName() }}</p>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif

        @if ($rsvpLocked)
            <div class="notice is-locked">RSVP changes are currently closed. Please contact your wedding planner for updates.</div>
        @endif

        <form class="card" method="POST" action="{{ route('public.rsvp.submit', ['token' => $guest->rsvp_token]) }}">
            @csrf
            <fieldset @disabled($rsvpLocked) style="border: 0; margin: 0; padding: 0;">
            @php
                $presenceValue = old('presence_confirmed', $guest->presence_confirmed === null ? '1' : ($guest->presence_confirmed ? '1' : '0'));
            @endphp

            <section class="section">
                <h2>Confirm your attendance</h2>
                <div class="presence-options">
                    <label class="presence-option">
                        <input type="radio" name="presence_confirmed" value="1" @checked((string) $presenceValue === '1') data-presence-radio>
                        Yes
                    </label>
                    <label class="presence-option">
                        <input type="radio" name="presence_confirmed" value="0" @checked((string) $presenceValue === '0') data-presence-radio>
                        No
                    </label>
                </div>
            </section>

            <div data-rsvp-details>

            <section class="section">
                <h2>Guests</h2>
                <div class="people">
                    @foreach ($subjects as $subject)
                        @php
                            $subjectKey = $subject['key'];
                            $additional = $subject['type'] === 'additional' ? (($guest->additional_guests ?? [])[$subject['index']] ?? []) : [];
                        @endphp
                        <article class="person-card">
                            <div>
                                <p class="person-title">{{ $subject['label'] }}</p>
                                <p class="person-subtitle">
                                    @if ($subject['type'] === 'primary')
                                        Main guest
                                    @elseif ($subject['type'] === 'partner')
                                        Partner / plus-one
                                    @else
                                        Additional guest
                                    @endif
                                </p>
                            </div>

                            <div class="person-fields">
                                @if ($subject['type'] === 'primary')
                                    <label><span>First name <span class="required-mark">*</span></span><input name="primary_first_name" value="{{ old('primary_first_name', $guest->primary_first_name) }}" required></label>
                                    <label><span>Last name <span class="required-mark">*</span></span><input name="primary_last_name" value="{{ old('primary_last_name', $guest->primary_last_name) }}" required></label>
                                @elseif ($subject['type'] === 'partner')
                                    <label><span>First name</span><input name="partner_first_name" value="{{ old('partner_first_name', $guest->partner_first_name) }}"></label>
                                    <label><span>Last name</span><input name="partner_last_name" value="{{ old('partner_last_name', $guest->partner_last_name) }}"></label>
                                @else
                                    <label><span>First name</span><input name="additional_guests[{{ $subject['index'] }}][first_name]" value="{{ old("additional_guests.{$subject['index']}.first_name", $additional['first_name'] ?? '') }}"></label>
                                    <label><span>Last name</span><input name="additional_guests[{{ $subject['index'] }}][last_name]" value="{{ old("additional_guests.{$subject['index']}.last_name", $additional['last_name'] ?? '') }}"></label>
                                    <label><span>Role</span><input name="additional_guests[{{ $subject['index'] }}][role]" value="{{ old("additional_guests.{$subject['index']}.role", $additional['role'] ?? '') }}"></label>
                                    @php
                                        $additionalType = old("additional_guests.{$subject['index']}.type", $additional['type'] ?? '');
                                        $additionalAge = old("additional_guests.{$subject['index']}.age", $additional['age'] ?? '');
                                        $needsHighChair = $additionalType === 'Child' && $additionalAge !== '' && (int) $additionalAge <= 3;
                                        $additionalHighChair = old("additional_guests.{$subject['index']}.high_chair", ! empty($additional['high_chair']));
                                    @endphp
                                    <div class="additional-type-age {{ $additionalType === 'Child' ? 'is-child' : '' }} {{ $needsHighChair ? 'needs-high-chair' : '' }}" data-additional-type-age>
                                        <label>
                                            <span>Type</span>
                                            <select name="additional_guests[{{ $subject['index'] }}][type]" data-additional-type-select>
                                                <option value="">Type</option>
                                                <option value="Adult" @selected($additionalType === 'Adult')>Adult</option>
                                                <option value="Child" @selected($additionalType === 'Child')>Child</option>
                                                <option value="Guest" @selected($additionalType === 'Guest')>Guest</option>
                                            </select>
                                        </label>
                                        <label class="additional-age-field">
                                            <span>Age</span>
                                            <select name="additional_guests[{{ $subject['index'] }}][age]" data-additional-age-select>
                                                <option value="">Age</option>
                                                @for ($age = 0; $age <= 18; $age++)
                                                    <option value="{{ $age }}" @selected((string) $additionalAge === (string) $age)>{{ $age }}</option>
                                                @endfor
                                            </select>
                                        </label>
                                        <label class="check high-chair-field">
                                            <input
                                                type="checkbox"
                                                name="additional_guests[{{ $subject['index'] }}][high_chair]"
                                                value="1"
                                                data-high-chair-checkbox
                                                @checked((bool) $additionalHighChair)
                                            >
                                            High chair needed
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="person-questions">
                                @foreach ($fields as $field)
                                    @continue(! ($field['enabled'] ?? false) || ($field['response_scope'] ?? 'aggregate') !== 'per_guest')
                                    @php
                                        $fieldValue = old('rsvp_response.' . $field['key'], $response[$field['key']] ?? null);
                                        $subjectValue = old(
                                            'rsvp_response.' . $field['key'] . '.' . $subjectKey,
                                            is_array($fieldValue) ? ($fieldValue[$subjectKey]['value'] ?? $fieldValue[$subjectKey] ?? null) : null
                                        );
                                    @endphp
                                    <label>
                                        <span>{{ $field['label'] }}</span>
                                        @if (($field['type'] ?? 'text') === 'select')
                                            <select name="rsvp_response[{{ $field['key'] }}][{{ $subjectKey }}]">
                                                <option value="">Select</option>
                                                @foreach (($field['options'] ?? []) as $option)
                                                    <option value="{{ $option }}" @selected($subjectValue === $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @elseif (($field['type'] ?? 'text') === 'checkbox')
                                            <span class="check"><input type="checkbox" name="rsvp_response[{{ $field['key'] }}][{{ $subjectKey }}]" value="1" @checked((bool) $subjectValue)> Yes</span>
                                        @else
                                            <textarea name="rsvp_response[{{ $field['key'] }}][{{ $subjectKey }}]">{{ $subjectValue }}</textarea>
                                        @endif
                                        @if ($field['help_text'])
                                            <p class="help">{{ $field['help_text'] }}</p>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <h2>Contact information</h2>
                <div class="grid">
                    <label><span>Email <span class="required-mark">*</span></span><input type="email" name="email" value="{{ old('email', $guest->email) }}" required></label>
                    <label><span>Phone <span class="required-mark">*</span></span><input name="phone" value="{{ old('phone', $guest->phone) }}" required></label>
                    <label><span>Address line 1</span><input name="address_line_1" value="{{ old('address_line_1', $guest->address_line_1) }}"></label>
                    <label><span>Address line 2</span><input name="address_line_2" value="{{ old('address_line_2', $guest->address_line_2) }}"></label>
                    <label><span>City</span><input name="city" value="{{ old('city', $guest->city) }}"></label>
                    <label><span>State</span><input name="state" value="{{ old('state', $guest->state) }}"></label>
                    <label><span>Zip</span><input name="postal_code" value="{{ old('postal_code', $guest->postal_code) }}"></label>
                    <label><span>Country</span><input name="country" value="{{ old('country', $guest->country) }}"></label>
                </div>
            </section>

            <section class="section">
                <h2>RSVP questions</h2>
                <div class="grid">
                    @foreach ($fields as $field)
                        @continue(! ($field['enabled'] ?? false))
                        @php
                            $value = old('rsvp_response.' . $field['key'], $response[$field['key']] ?? null);
                        @endphp
                        @continue(($field['response_scope'] ?? 'aggregate') === 'per_guest')
                        <label>
                            <span>{{ $field['label'] }}</span>
                            @if (($field['type'] ?? 'text') === 'select')
                                <select name="rsvp_response[{{ $field['key'] }}]">
                                    <option value="">Select</option>
                                    @foreach (($field['options'] ?? []) as $option)
                                        <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif (($field['type'] ?? 'text') === 'checkbox')
                                <span class="check"><input type="checkbox" name="rsvp_response[{{ $field['key'] }}]" value="1" @checked((bool) $value)> Yes</span>
                            @else
                                <textarea name="rsvp_response[{{ $field['key'] }}]">{{ $value }}</textarea>
                            @endif
                            @if ($field['help_text'])
                                <p class="help">{{ $field['help_text'] }}</p>
                            @endif
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="section">
                @if ($rsvpLocked)
                    <p class="help">This RSVP is read-only for guests. Only the wedding planner can make changes now.</p>
                @else
                    <button type="submit" class="button">Save RSVP</button>
                @endif
            </section>
            </div>
            <section class="section" data-rsvp-decline-submit style="display: none;">
                @if ($rsvpLocked)
                    <p class="help">This RSVP is read-only for guests. Only the wedding planner can make changes now.</p>
                @else
                    <button type="submit" class="button">Save RSVP</button>
                @endif
            </section>
            </fieldset>
        </form>
    </main>
    <script>
        const syncPresenceDetails = () => {
            const selectedPresence = document.querySelector('[data-presence-radio]:checked')?.value || '1';
            const details = document.querySelector('[data-rsvp-details]');
            const declineSubmit = document.querySelector('[data-rsvp-decline-submit]');
            const showDetails = selectedPresence === '1';

            if (details) {
                details.style.display = showDetails ? '' : 'none';
                details.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = ! showDetails;
                });
            }

            if (declineSubmit) {
                declineSubmit.style.display = showDetails ? 'none' : '';
            }
        };

        document.querySelectorAll('[data-presence-radio]').forEach((field) => {
            field.addEventListener('change', syncPresenceDetails);
        });

        syncPresenceDetails();

        document.querySelectorAll('[data-additional-type-age]').forEach((field) => {
            const typeSelect = field.querySelector('[data-additional-type-select]');
            const ageSelect = field.querySelector('[data-additional-age-select]');
            const highChairCheckbox = field.querySelector('[data-high-chair-checkbox]');
            const syncAgeField = () => {
                const isChild = typeSelect.value === 'Child';
                const age = Number(ageSelect?.value ?? '');
                const needsHighChair = isChild && ageSelect?.value !== '' && age >= 0 && age <= 3;
                field.classList.toggle('is-child', isChild);
                field.classList.toggle('needs-high-chair', needsHighChair);

                if (! isChild && ageSelect) {
                    ageSelect.value = '';
                }

                if (! needsHighChair && highChairCheckbox) {
                    highChairCheckbox.checked = false;
                }
            };

            typeSelect.addEventListener('change', syncAgeField);
            ageSelect?.addEventListener('change', syncAgeField);
            syncAgeField();
        });
    </script>
</body>
</html>
