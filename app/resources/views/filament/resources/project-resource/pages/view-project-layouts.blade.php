<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $plans = $this->getSeatingPlans();
        $summary = $this->getLayoutSummary();
        $planTypeOptions = $this->getPlanTypeOptions();
        $initialTableTypeOptions = $this->getInitialTableTypeOptions();
        $isCustomer = auth()->user()?->isCustomer();
    @endphp

    <style>
        .wm-layouts-page { display: flex; flex-direction: column; gap: 1rem; }
        .wm-layouts-shell { width: min(1160px, calc(100% - 2rem)); margin: 0 auto; display: grid; gap: 1rem; }
        .wm-event-card { border: 1px solid var(--cup-border-soft, #e8e3dc); border-radius: 1.35rem; background: rgba(255,255,255,.92); box-shadow: 0 20px 42px rgba(45,42,38,.06); }
        .wm-event-top { display: flex; flex-direction: column; gap: .85rem; align-items: start; padding: .9rem 1rem 1rem; }
        .wm-event-top-head { width: 100%; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .9rem 1rem; align-items: center; }
        .wm-event-top-title { margin: 0; font-family: 'Cinzel', serif; font-size: clamp(1.2rem,1.8vw,1.65rem); line-height: 1.08; color: #2d2a26; }
        .wm-event-top-meta { display: flex; flex-wrap: wrap; gap: .6rem .95rem; margin-top: .4rem; color: #746d66; font-size: .86rem; line-height: 1.5; }
        .wm-event-top-meta span:not(:last-child)::after { content: "•"; margin-left: .95rem; color: #c9a96a; }
        .wm-event-top-side { display: flex; align-items: center; gap: .55rem; }
        .wm-event-summary-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 6rem; padding: .62rem .78rem; border-radius: 1rem; border: 1px solid rgba(201,169,106,.22); background: rgba(255,255,255,.85); color: #5f5953; }
        .wm-event-summary-chip-label { margin: 0; font-size: .62rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #9a8f82; }
        .wm-event-summary-chip-value { margin: .16rem 0 0; font-size: .98rem; font-weight: 700; color: #2d2a26; }
        .wm-event-countdown { min-width: 11.5rem; padding: .62rem .82rem; border-radius: 1rem; background: linear-gradient(160deg, rgba(46,74,98,.96), rgba(36,60,81,.98)); color: #f7f3ed; }
        .wm-event-countdown-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .wm-event-countdown-label { margin: 0; font-size: .66rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.64); }
        .wm-event-countdown-edit { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border: 0; border-radius: 999px; background: rgba(255,255,255,.10); color: rgba(255,255,255,.86); cursor: pointer; }
        .wm-event-countdown-value { margin: .18rem 0 0; color: #fff; font-size: 1.08rem; font-weight: 700; }
        .wm-event-countdown-meta { margin: .1rem 0 0; color: rgba(255,255,255,.72); font-size: .8rem; }
        .wm-event-workspace { display: flex; align-items: center; gap: .4rem; overflow-x: auto; width: 100%; padding: .28rem; border-radius: 1.2rem; background: rgba(247,243,237,.96); scrollbar-width: none; border: 1px solid #ece5dd; }
        .wm-event-workspace-link { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; min-height: 2.45rem; padding: 0 .88rem; border-radius: 999px; color: #746d66; font-size: .69rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; white-space: nowrap; text-decoration: none; }
        .wm-event-workspace-link.is-active { background: rgba(122,143,123,.14); color: #2d7a39; }
        .wm-event-top-date-tools { width: 100%; }
        .wm-event-date-editor { display: grid; gap: .85rem; width: 100%; max-width: 38rem; padding: 1rem; border-radius: 1rem; background: #fbf8f4; border: 1px solid #ece5dd; }
        .wm-event-date-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .wm-event-date-grid.is-single { grid-template-columns: minmax(0, 1fr); max-width: 16rem; }
        .wm-event-date-label { display: block; margin-bottom: .35rem; color: #5e5852; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .wm-event-date-input { width: 100%; min-height: 2.9rem; border-radius: .95rem; border: 1px solid #ddd2c5; background: #fff; padding: 0 .95rem; color: #2d2a26; }
        .wm-event-date-actions { display: flex; flex-wrap: wrap; gap: .7rem; }
        .wm-event-date-toggle { display: inline-flex; align-items: center; gap: .6rem; color: #4d473f; font-weight: 600; }
        .wm-layouts-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem; }
        .wm-layouts-title { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-layouts-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
        .wm-layouts-stat { padding: 1rem; }
        .wm-layouts-stat-label { margin: 0; color: #8a8178; font-size: .7rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .wm-layouts-stat-value { margin: .25rem 0 0; color: #2d2a26; font-size: 1.45rem; font-weight: 900; }
        .wm-layouts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); gap: 1rem; }
        .wm-layout-card { display: grid; gap: .85rem; padding: 1rem; }
        .wm-layout-preview { overflow: hidden; border: 1px solid #e5d9cd; border-radius: .8rem; background: #faf7f2; aspect-ratio: 14 / 9; }
        .wm-layout-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .wm-layout-preview-empty { height: 100%; display: grid; place-items: center; color: #8b8279; font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .wm-layout-card-head { display: flex; justify-content: space-between; gap: .75rem; align-items: start; }
        .wm-layout-card-title { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 900; line-height: 1.3; }
        .wm-layout-card-type { display: inline-flex; min-height: 1.8rem; align-items: center; padding: 0 .65rem; border-radius: 999px; background: #f4eee6; color: #6f5830; font-size: .68rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-layout-card-meta { display: flex; flex-wrap: wrap; gap: .45rem; }
        .wm-layout-chip { display: inline-flex; align-items: center; min-height: 1.75rem; padding: 0 .6rem; border-radius: 999px; background: rgba(122,143,123,.12); color: #48634c; font-size: .7rem; font-weight: 800; }
        .wm-layout-report { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; }
        .wm-layout-report-item { padding: .55rem .65rem; border: 1px solid #ece3d9; border-radius: .7rem; background: #fffaf5; }
        .wm-layout-report-label { margin: 0; color: #8b8279; font-size: .62rem; font-weight: 900; letter-spacing: .11em; text-transform: uppercase; }
        .wm-layout-report-value { margin: .12rem 0 0; color: #2d2a26; font-size: 1rem; font-weight: 900; }
        .wm-layout-notes { margin: 0; color: #686058; font-size: .9rem; line-height: 1.6; }
        .wm-layout-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .55rem; }
        .wm-layout-empty { padding: 1.2rem; color: #857d76; line-height: 1.6; }
        .wm-layout-modal-backdrop { position: fixed; inset: 0; z-index: 40; background: rgba(31,25,20,.34); }
        .wm-layout-modal { position: fixed; top: 50%; left: 50%; z-index: 50; width: min(42rem, calc(100vw - 2rem)); max-height: calc(100vh - 2rem); overflow: auto; transform: translate(-50%, -50%); border-radius: 1.1rem; border: 1px solid #d9ccc0; background: rgba(255,255,255,.98); box-shadow: 0 24px 60px rgba(24,18,14,.18); padding: 1.2rem; }
        .wm-layout-modal-head { display: flex; justify-content: space-between; gap: 1rem; align-items: start; margin-bottom: 1rem; }
        .wm-layout-modal-title { margin: 0; color: #2d2a26; font-size: 1.05rem; font-weight: 900; }
        .wm-layout-close { width: 2.25rem; height: 2.25rem; border: 0; border-radius: 999px; background: #f4eee6; color: #6a6158; cursor: pointer; }
        .wm-layout-form { display: grid; gap: 1rem; }
        .wm-layout-field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .wm-layout-field label { display: block; margin-bottom: .45rem; color: #5e5852; font-size: .75rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .wm-layout-input, .wm-layout-select, .wm-layout-textarea { width: 100%; min-height: 2.75rem; border-radius: .8rem; border: 1px solid #ddd2c5; background: #fff; padding: .7rem .85rem; color: #2d2a26; }
        .wm-layout-textarea { min-height: 6rem; resize: vertical; }
        .wm-layout-modal-actions { display: flex; justify-content: flex-end; gap: .7rem; margin-top: 1rem; }
        @media (max-width: 760px) {
            .wm-layouts-shell { width: min(100%, calc(100% - 1rem)); }
            .wm-event-top-head, .wm-layouts-summary { grid-template-columns: 1fr; }
            .wm-event-top-side, .wm-layouts-toolbar { align-items: stretch; flex-direction: column; }
        }
    </style>

    <div class="wm-layouts-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'layouts',
        ])

        <div class="wm-layouts-shell">
            <section class="wm-layouts-summary">
                <article class="wm-event-card wm-layouts-stat">
                    <p class="wm-layouts-stat-label">Layouts</p>
                    <p class="wm-layouts-stat-value">{{ $summary['plans'] }}</p>
                </article>
                <article class="wm-event-card wm-layouts-stat">
                    <p class="wm-layouts-stat-label">Tables</p>
                    <p class="wm-layouts-stat-value">{{ $summary['tables'] }}</p>
                </article>
                <article class="wm-event-card wm-layouts-stat">
                    <p class="wm-layouts-stat-label">Assigned seats</p>
                    <p class="wm-layouts-stat-value">{{ $summary['assigned'] }}</p>
                </article>
            </section>

            <section class="wm-event-card wm-layouts-toolbar">
                <h2 class="wm-layouts-title">Seating plans</h2>
                @if (! $isCustomer)
                    <x-filament::button wire:click="startCreateSeatingPlan">
                        New layout
                    </x-filament::button>
                @endif
            </section>

            @if ($plans->isEmpty())
                <section class="wm-event-card wm-layout-empty">
                    @if ($isCustomer)
                        Your wedding planner will prepare the seating plan here. Once it is ready, you will be able to assign guests to their seats.
                    @else
                        No layouts yet. Create the first seating plan for ceremony, dinner, lunch or another event moment.
                    @endif
                </section>
            @else
                <section class="wm-layouts-grid">
                    @foreach ($plans as $plan)
                        @php
                            $planStats = $this->getPlanStats($plan);
                            $previewUrl = $this->getPlanPreviewUrl($plan);
                        @endphp
                        <article class="wm-event-card wm-layout-card">
                            <div class="wm-layout-card-head">
                                <div>
                                    <h3 class="wm-layout-card-title">{{ $plan->name }}</h3>
                                    <span class="wm-layout-card-type">{{ $planTypeOptions[$plan->plan_type] ?? ($plan->plan_type ?: 'Layout') }}</span>
                                </div>
                            </div>

                            <div class="wm-layout-preview">
                                @if ($previewUrl)
                                    <img src="{{ $previewUrl }}" alt="{{ $plan->name }} preview">
                                @else
                                    <div class="wm-layout-preview-empty">No preview yet</div>
                                @endif
                            </div>

                            <div class="wm-layout-card-meta">
                                <span class="wm-layout-chip">{{ $plan->tables->count() }} tables</span>
                                <span class="wm-layout-chip">{{ $plan->updated_at?->format('d/m/Y H:i') ?: 'Not updated' }}</span>
                            </div>

                            <div class="wm-layout-report">
                                <div class="wm-layout-report-item">
                                    <p class="wm-layout-report-label">Tables</p>
                                    <p class="wm-layout-report-value">{{ $planStats['tables'] }}</p>
                                </div>
                                <div class="wm-layout-report-item">
                                    <p class="wm-layout-report-label">Total seats</p>
                                    <p class="wm-layout-report-value">{{ $planStats['seats'] }}</p>
                                </div>
                                <div class="wm-layout-report-item">
                                    <p class="wm-layout-report-label">Assigned</p>
                                    <p class="wm-layout-report-value">{{ $planStats['assigned'] }}</p>
                                </div>
                                <div class="wm-layout-report-item">
                                    <p class="wm-layout-report-label">Empty</p>
                                    <p class="wm-layout-report-value">{{ $planStats['empty'] }}</p>
                                </div>
                            </div>

                            @if ($plan->notes)
                                <p class="wm-layout-notes">{{ $plan->notes }}</p>
                            @endif

                            <div class="wm-layout-actions">
                                @if (! $isCustomer)
                                    <x-filament::button color="gray" size="sm" wire:click="editSeatingPlan({{ $plan->id }})">
                                        Edit details
                                    </x-filament::button>
                                    <x-filament::button
                                        tag="a"
                                        size="sm"
                                        href="{{ \App\Filament\Resources\ProjectResource::getUrl('layout-edit', ['record' => $record, 'seatingPlan' => $plan]) }}"
                                    >
                                        Open editor
                                    </x-filament::button>
                                @endif
                                <x-filament::button
                                    tag="a"
                                    color="gray"
                                    size="sm"
                                    href="{{ \App\Filament\Resources\ProjectResource::getUrl('layout-assign', ['record' => $record, 'seatingPlan' => $plan]) }}"
                                >
                                    Assign Seating
                                </x-filament::button>
                                <x-filament::button
                                    tag="a"
                                    color="gray"
                                    size="sm"
                                    href="{{ route('admin.projects.layouts.seating-plan.pdf', ['project' => $record, 'seatingPlan' => $plan]) }}"
                                    target="_blank"
                                >
                                    PDF
                                </x-filament::button>
                                @if (! $isCustomer)
                                    <x-filament::button color="danger" size="sm" wire:click="promptDeleteSeatingPlan({{ $plan->id }})">
                                        Delete
                                    </x-filament::button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif
        </div>

        @if ($showSeatingPlanEditor)
            <div class="wm-layout-modal-backdrop" wire:click="closeSeatingPlanEditor"></div>
            <div class="wm-layout-modal" role="dialog" aria-modal="true">
                <div class="wm-layout-modal-head">
                    <h3 class="wm-layout-modal-title">{{ $editingSeatingPlanId ? 'Edit layout' : 'New layout' }}</h3>
                    <button type="button" class="wm-layout-close" wire:click="closeSeatingPlanEditor">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <div class="wm-layout-form">
                    <div class="wm-layout-field">
                        <label for="layout-name">Name</label>
                        <input id="layout-name" class="wm-layout-input" type="text" wire:model="seatingPlanForm.name">
                    </div>
                    <div class="wm-layout-field">
                        <label for="layout-type">Type</label>
                        <select id="layout-type" class="wm-layout-select" wire:model="seatingPlanForm.plan_type">
                            <option value="">Select type</option>
                            @foreach ($planTypeOptions as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (! $editingSeatingPlanId)
                        <div class="wm-layout-field-grid">
                            <div class="wm-layout-field">
                                <label for="layout-table-type">Initial tables</label>
                                <select id="layout-table-type" class="wm-layout-select" wire:model="seatingPlanForm.initial_table_type">
                                    @foreach ($initialTableTypeOptions as $type => $label)
                                        <option value="{{ $type }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="wm-layout-field">
                                <label for="layout-table-count">How many tables</label>
                                <input id="layout-table-count" class="wm-layout-input" type="number" min="0" max="200" wire:model="seatingPlanForm.initial_table_count">
                            </div>
                        </div>
                    @endif
                    <div class="wm-layout-field">
                        <label for="layout-notes">Notes</label>
                        <textarea id="layout-notes" class="wm-layout-textarea" wire:model="seatingPlanForm.notes"></textarea>
                    </div>
                </div>

                <div class="wm-layout-modal-actions">
                    <x-filament::button color="gray" wire:click="closeSeatingPlanEditor">
                        Cancel
                    </x-filament::button>
                    <x-filament::button wire:click="saveSeatingPlan">
                        {{ $editingSeatingPlanId ? 'Update layout' : 'Create layout' }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        @if ($confirmDeleteSeatingPlanId)
            <div class="wm-layout-modal-backdrop" wire:click="cancelDeleteSeatingPlan"></div>
            <div class="wm-layout-modal" role="dialog" aria-modal="true">
                <p class="wm-layout-notes">Deleting this layout will also remove its tables and guest assignments.</p>
                <div class="wm-layout-modal-actions">
                    <x-filament::button color="gray" wire:click="cancelDeleteSeatingPlan">
                        Cancel
                    </x-filament::button>
                    <x-filament::button color="danger" wire:click="confirmDeleteSeatingPlan">
                        Delete layout
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
