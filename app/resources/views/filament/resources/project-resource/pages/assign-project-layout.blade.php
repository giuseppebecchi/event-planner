<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $plan = $this->currentSeatingPlan;
        $layoutsUrl = \App\Filament\Resources\ProjectResource::getUrl('layouts', ['record' => $record]);
        $editorUrl = \App\Filament\Resources\ProjectResource::getUrl('layout-edit', ['record' => $record, 'seatingPlan' => $plan]);
        $initialTables = $this->getEditorTables();
        $initialElements = $this->getLayoutElements();
        $guests = $this->getAssignableGuests();
        $backgroundUrl = $this->getBackgroundImageUrl();
    @endphp

    <style>
        body:has(.wm-seat-assigner) { overflow: hidden !important; }
        .fi-layout, .fi-main, .fi-page, .fi-page-content { overflow: hidden !important; }
        .fi-main { padding-inline: 0 !important; }
        .fi-page-content { padding: 0 !important; }
        .wm-seat-assigner { position: fixed; inset: 0; z-index: 40; width: 100vw; height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) 24rem; background: #f3eee7; color: #2d2a26; overflow: hidden; }
        .wm-seat-stage { position: relative; display: grid; grid-template-rows: auto minmax(0, 1fr); min-width: 0; }
        .wm-seat-topbar { min-height: 3.8rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .65rem 1rem; background: rgba(255,255,255,.9); }
        .wm-seat-title { min-width: 0; }
        .wm-seat-title h1 { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .wm-seat-title p { margin: .18rem 0 0; color: #7c746c; font-size: .82rem; }
        .wm-seat-actions { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; justify-content: flex-end; }
        .wm-seat-button { min-height: 2.3rem; border: 1px solid #d8cab9; border-radius: .65rem; background: #fff; color: #504841; padding: 0 .75rem; font-weight: 800; font-size: .78rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .wm-seat-button.is-tool { width: 2.3rem; padding: 0; }
        .wm-seat-canvas-wrap { min-height: 0; padding: 1rem; overflow: hidden; }
        .wm-seat-canvas { width: 100%; height: 100%; border: 1px solid #d8cab9; border-radius: 1rem; background: #faf7f2; box-shadow: inset 0 0 0 1px rgba(255,255,255,.78), 0 18px 48px rgba(45,42,38,.08); touch-action: none; user-select: none; }
        .wm-seat-grid-line { stroke: rgba(121,112,102,.13); stroke-width: 1; }
        .wm-seat-table { cursor: pointer; }
        .wm-seat-table-shape { fill: #fffdf9; stroke: #7a8f7b; stroke-width: 2.5; filter: drop-shadow(0 8px 10px rgba(45,42,38,.12)); }
        .wm-seat-table-glow { fill: #d9b86f; opacity: 0; filter: drop-shadow(0 0 18px rgba(201,169,106,.62)); pointer-events: none; }
        .wm-seat-table.is-selected .wm-seat-table-glow { opacity: .35; }
        .wm-seat-table.is-selected .wm-seat-table-shape { fill: #fff4d8; stroke: #c9a96a; stroke-width: 4; filter: drop-shadow(0 14px 18px rgba(201,169,106,.28)); }
        .wm-layout-element-shape { stroke: #a88f62; stroke-width: 2; stroke-dasharray: 6 4; filter: drop-shadow(0 8px 10px rgba(45,42,38,.08)); pointer-events: none; }
        .wm-layout-label { fill: #4b433b; font-size: 15px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; pointer-events: none; }
        .wm-seat-hit-area { fill: transparent; stroke: transparent; pointer-events: all; }
        .wm-seat-label { fill: #2d2a26; font-size: 13px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; pointer-events: none; }
        .wm-seat-chair { cursor: pointer; }
        .wm-seat-chair-seat { fill: #fffaf2; stroke: #9d8451; stroke-width: 1.4; }
        .wm-seat-chair-back { fill: #d8c298; stroke: #9d8451; stroke-width: 1.4; }
        .wm-seat-chair.is-occupied .wm-seat-chair-seat { fill: #dfeedd; stroke: #5f8f62; }
        .wm-seat-chair.is-occupied .wm-seat-chair-back { fill: #a9cfa9; stroke: #5f8f62; }
        .wm-seat-chair.is-active .wm-seat-chair-seat, .wm-seat-chair.is-active .wm-seat-chair-back { stroke: #c9a96a; stroke-width: 2.5; }
        .wm-seat-number { fill: #4f463d; font-size: 9px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; pointer-events: none; }
        .wm-seat-tag { pointer-events: none; }
        .wm-seat-tag-bg { fill: #fffaf2; stroke: #c9a96a; stroke-width: 1.3; filter: drop-shadow(0 5px 7px rgba(45,42,38,.12)); }
        .wm-seat-tag-text { fill: #2d2a26; font-size: 10px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; }
        .wm-seat-sidebar { min-width: 0; height: 100vh; border-left: 1px solid #ded3c7; background: rgba(255,255,255,.94); overflow-y: auto; overscroll-behavior: contain; }
        .wm-seat-panel { padding: .85rem 1rem; border-bottom: 1px solid #ece3d9; display: grid; gap: .55rem; }
        .wm-seat-panel h2 { margin: 0; color: #2d2a26; font-size: .78rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
        .wm-seat-muted { margin: 0; color: #8b8279; line-height: 1.55; font-size: .86rem; }
        .wm-seat-save-state { color: #8b8279; font-size: .78rem; font-weight: 800; }
        .wm-seat-select { width: 100%; min-height: 2rem; border: 1px solid #ddd2c5; border-radius: .55rem; background: #fff; color: #2d2a26; padding: 0 .5rem; font-size: .8rem; }
        .wm-seat-row { display: grid; grid-template-columns: 1.75rem minmax(0, 1fr); gap: .4rem; align-items: center; padding: .18rem .25rem; border: 1px solid transparent; border-radius: .55rem; }
        .wm-seat-row.is-assigned { border-color: #7fb47b; background: #d7ecd4; }
        .wm-seat-row.is-empty { border-color: #ead8a3; background: #fff7dc; }
        .wm-seat-row.is-active { border-color: #c9a96a; background: #fff8e8; }
        .wm-seat-index { display: inline-flex; align-items: center; justify-content: center; width: 1.65rem; height: 1.65rem; border-radius: 999px; background: #f4eee6; color: #6f5830; font-size: .72rem; font-weight: 900; }
        .wm-seat-row.is-assigned .wm-seat-index { background: #5f985c; color: #fff; }
        .wm-seat-guest-list { display: grid; gap: .3rem; }
        .wm-seat-guest-item { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .42rem .55rem; border: 1px solid #ece3d9; border-radius: .65rem; background: #fffaf5; }
        .wm-seat-guest-item.is-assigned { background: #edf6eb; border-color: #c9dfc7; }
        .wm-seat-guest-name { margin: 0; color: #2d2a26; font-size: .84rem; font-weight: 850; }
        .wm-seat-guest-group { margin: .12rem 0 0; color: #8b8279; font-size: .72rem; }
        .wm-seat-guest-status { color: #6f8f64; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    </style>

    <div
        class="wm-seat-assigner"
        x-data="seatingAssigner({
            tables: @js($initialTables),
            elements: @js($initialElements),
            guests: @js($guests),
            backgroundUrl: @js($backgroundUrl),
            viewport: @js($this->getViewportState()),
        })"
    >
        <section class="wm-seat-stage">
            <header class="wm-seat-topbar">
                <div class="wm-seat-title">
                    <h1>{{ $plan->name }}</h1>
                    <p>Assign seating · <span x-text="assignedCount()"></span>/<span x-text="totalSeats()"></span> seats assigned</p>
                </div>
                <div class="wm-seat-actions">
                    <a class="wm-seat-button" href="{{ $layoutsUrl }}">Back</a>
                    <a class="wm-seat-button" href="{{ $editorUrl }}">Open editor</a>
                    <button type="button" class="wm-seat-button is-tool" x-on:click="zoomOut">-</button>
                    <span class="wm-seat-button" x-text="Math.round(zoom * 100) + '%'"></span>
                    <button type="button" class="wm-seat-button is-tool" x-on:click="zoomIn">+</button>
                    <span class="wm-seat-save-state" x-text="saveStatus"></span>
                </div>
            </header>

            <div class="wm-seat-canvas-wrap">
                <svg
                    class="wm-seat-canvas"
                    x-ref="svg"
                    viewBox="0 0 1400 900"
                    x-on:wheel.prevent="zoomWheel($event)"
                    x-on:pointerdown="startPan($event)"
                    x-on:pointermove="pointerMove($event)"
                    x-on:pointerup="pointerUp()"
                    x-on:pointerleave="pointerUp()"
                >
                    <g x-ref="viewport" x-bind:transform="viewportTransform()">
                        <template x-for="x in gridColumns" :key="'x' + x">
                            <line class="wm-seat-grid-line" :x1="x" y1="0" :x2="x" y2="900"></line>
                        </template>
                        <template x-for="y in gridRows" :key="'y' + y">
                            <line class="wm-seat-grid-line" x1="0" :y1="y" x2="1400" :y2="y"></line>
                        </template>

                        <image x-show="backgroundUrl" :href="backgroundUrl" x="0" y="0" width="1400" height="900" preserveAspectRatio="xMidYMid slice" opacity=".74"></image>

                        @foreach ($initialElements as $element)
                            <g
                                transform="translate({{ $element['center_x'] }}, {{ $element['center_y'] }}) rotate({{ $element['rotation'] }})"
                                x-bind:transform="elementTransform({{ $element['id'] }})"
                            >
                                <rect
                                    class="wm-layout-element-shape"
                                    x="{{ -$element['width'] / 2 }}"
                                    y="{{ -$element['height'] / 2 }}"
                                    width="{{ $element['width'] }}"
                                    height="{{ $element['height'] }}"
                                    rx="8"
                                    fill="{{ $element['background_color'] }}"
                                    x-show="elementById({{ $element['id'] }}).element_type === 'space' && elementById({{ $element['id'] }}).shape !== 'circle'"
                                    x-bind:x="-elementById({{ $element['id'] }}).width / 2"
                                    x-bind:y="-elementById({{ $element['id'] }}).height / 2"
                                    x-bind:width="elementById({{ $element['id'] }}).width"
                                    x-bind:height="elementById({{ $element['id'] }}).height"
                                    x-bind:fill="elementById({{ $element['id'] }}).background_color || 'transparent'"
                                    @if ($element['element_type'] !== 'space' || $element['shape'] === 'circle') style="display: none;" @endif
                                ></rect>
                                <ellipse
                                    class="wm-layout-element-shape"
                                    cx="0"
                                    cy="0"
                                    rx="{{ $element['width'] / 2 }}"
                                    ry="{{ $element['height'] / 2 }}"
                                    fill="{{ $element['background_color'] }}"
                                    x-show="elementById({{ $element['id'] }}).element_type === 'space' && elementById({{ $element['id'] }}).shape === 'circle'"
                                    x-bind:rx="elementById({{ $element['id'] }}).width / 2"
                                    x-bind:ry="elementById({{ $element['id'] }}).height / 2"
                                    x-bind:fill="elementById({{ $element['id'] }}).background_color || 'transparent'"
                                    @if ($element['element_type'] !== 'space' || $element['shape'] !== 'circle') style="display: none;" @endif
                                ></ellipse>
                                <text class="wm-layout-label" x="0" y="0" x-text="elementById({{ $element['id'] }}).label">{{ $element['label'] }}</text>
                            </g>
                        @endforeach

                        @foreach ($initialTables as $table)
                            <g
                                class="wm-seat-table"
                                x-bind:class="{ 'is-selected': selectedId === {{ $table['id'] }} }"
                                transform="translate({{ $table['center_x'] }}, {{ $table['center_y'] }}) rotate({{ $table['rotation'] }})"
                                x-on:click.stop="selectTable({{ $table['id'] }})"
                            >
                                <rect
                                    class="wm-seat-hit-area"
                                    x="{{ -($table['primary_dimension'] / 2) - 18 }}"
                                    y="-28"
                                    width="{{ $table['primary_dimension'] + 36 }}"
                                    height="74"
                                    rx="18"
                                    x-show="isChairRow(tableById({{ $table['id'] }}))"
                                    @if ($table['table_type'] !== 'chair_row') style="display: none;" @endif
                                ></rect>
                                <ellipse
                                    class="wm-seat-table-glow"
                                    cx="0"
                                    cy="0"
                                    rx="{{ ($table['primary_dimension'] / 2) + 18 }}"
                                    ry="{{ ($table['secondary_dimension'] / 2) + 18 }}"
                                    x-show="isRound(tableById({{ $table['id'] }}))"
                                    @if (! in_array($table['table_type'], ['round', 'oval'], true)) style="display: none;" @endif
                                ></ellipse>
                                <rect
                                    class="wm-seat-table-glow"
                                    x="{{ -($table['primary_dimension'] / 2) - 18 }}"
                                    y="{{ -($table['secondary_dimension'] / 2) - 18 }}"
                                    width="{{ $table['primary_dimension'] + 36 }}"
                                    height="{{ $table['secondary_dimension'] + 36 }}"
                                    rx="18"
                                    x-show="isBoxTable(tableById({{ $table['id'] }}))"
                                    @if (in_array($table['table_type'], ['round', 'oval', 'long_table', 'chair_row'], true)) style="display: none;" @endif
                                ></rect>
                                <path
                                    class="wm-seat-table-glow"
                                    x-show="isLongTable(tableById({{ $table['id'] }}))"
                                    x-bind:d="longTablePath(tableById({{ $table['id'] }}), 18)"
                                    @if ($table['table_type'] !== 'long_table') style="display: none;" @endif
                                ></path>
                                <ellipse
                                    class="wm-seat-table-shape"
                                    cx="0"
                                    cy="0"
                                    rx="{{ $table['primary_dimension'] / 2 }}"
                                    ry="{{ $table['secondary_dimension'] / 2 }}"
                                    x-show="isRound(tableById({{ $table['id'] }}))"
                                    @if (! in_array($table['table_type'], ['round', 'oval'], true)) style="display: none;" @endif
                                ></ellipse>
                                <rect
                                    class="wm-seat-table-shape"
                                    x="{{ -$table['primary_dimension'] / 2 }}"
                                    y="{{ -$table['secondary_dimension'] / 2 }}"
                                    width="{{ $table['primary_dimension'] }}"
                                    height="{{ $table['secondary_dimension'] }}"
                                    rx="7"
                                    x-show="isBoxTable(tableById({{ $table['id'] }}))"
                                    @if (in_array($table['table_type'], ['round', 'oval', 'long_table', 'chair_row'], true)) style="display: none;" @endif
                                ></rect>
                                <path
                                    class="wm-seat-table-shape"
                                    x-show="isLongTable(tableById({{ $table['id'] }}))"
                                    x-bind:d="longTablePath(tableById({{ $table['id'] }}))"
                                    @if ($table['table_type'] !== 'long_table') style="display: none;" @endif
                                ></path>

                                @for ($seatIndex = 0; $seatIndex < 160; $seatIndex++)
                                    <g
                                        class="wm-seat-chair"
                                        x-show="hasSeat(tableById({{ $table['id'] }}), {{ $seatIndex }})"
                                        x-bind:class="{ 'is-occupied': isSeatOccupied(tableById({{ $table['id'] }}), {{ $seatIndex }}), 'is-active': isActiveSeat({{ $table['id'] }}, {{ $seatIndex + 1 }}) }"
                                        x-bind:transform="seatTransform(tableById({{ $table['id'] }}), {{ $seatIndex }})"
                                        x-on:click.stop="selectSeat({{ $table['id'] }}, {{ $seatIndex + 1 }})"
                                        style="display: none;"
                                    >
                                        <rect class="wm-seat-chair-seat" x="-8" y="-6" width="16" height="14" rx="4"></rect>
                                        <rect class="wm-seat-chair-back" x="-10" y="-14" width="20" height="7" rx="3"></rect>
                                        <line class="wm-seat-chair-seat" x1="-6" y1="9" x2="-6" y2="13"></line>
                                        <line class="wm-seat-chair-seat" x1="6" y1="9" x2="6" y2="13"></line>
                                        <text
                                            class="wm-seat-number"
                                            x="0"
                                            y="1"
                                            x-bind:transform="isChairRow(tableById({{ $table['id'] }})) ? 'rotate(180)' : ''"
                                            x-text="{{ $seatIndex + 1 }}"
                                        ></text>
                                    </g>
                                    <g
                                        class="wm-seat-tag"
                                        x-show="! isChairRow(tableById({{ $table['id'] }})) && seatGuestShort(tableById({{ $table['id'] }}), {{ $seatIndex + 1 }})"
                                        x-bind:transform="seatTagTransform(tableById({{ $table['id'] }}), {{ $seatIndex }})"
                                        style="display: none;"
                                    >
                                        <rect
                                            class="wm-seat-tag-bg"
                                            x="-34"
                                            y="-10"
                                            width="68"
                                            height="20"
                                            rx="8"
                                            x-bind:width="seatTagWidth(tableById({{ $table['id'] }}), {{ $seatIndex + 1 }})"
                                            x-bind:x="-seatTagWidth(tableById({{ $table['id'] }}), {{ $seatIndex + 1 }}) / 2"
                                        ></rect>
                                        <text class="wm-seat-tag-text" x="0" y="1" x-text="seatGuestShort(tableById({{ $table['id'] }}), {{ $seatIndex + 1 }})"></text>
                                    </g>
                                @endfor

                                <text
                                    class="wm-seat-label"
                                    x="0"
                                    y="0"
                                    x-bind:y="isChairRow(tableById({{ $table['id'] }})) ? 34 : 0"
                                >{{ $table['name'] }}</text>
                            </g>
                        @endforeach
                    </g>
                </svg>
            </div>
        </section>

        <aside class="wm-seat-sidebar">
            <section class="wm-seat-panel" x-show="! selectedTable()">
                <h2>Table seats</h2>
                <p class="wm-seat-muted">Select a table to assign guests to its seats.</p>
            </section>

            <template x-if="selectedTable()">
                <section class="wm-seat-panel">
                    <h2 x-text="selectedTable().name"></h2>
                    <p class="wm-seat-muted"><span x-text="tableAssignedCount(selectedTable())"></span>/<span x-text="seats(selectedTable()).length"></span> seats assigned</p>
                    <template x-for="seat in seats(selectedTable())" :key="selectedTable().id + '-' + seat.number">
                        <div class="wm-seat-row" :class="{ 'is-active': isActiveSeat(selectedTable().id, seat.number), 'is-assigned': Boolean(seatGuestKey(selectedTable(), seat.number)), 'is-empty': ! seatGuestKey(selectedTable(), seat.number) }" :id="'seat-row-' + selectedTable().id + '-' + seat.number">
                            <button type="button" class="wm-seat-index" x-on:click="selectSeat(selectedTable().id, seat.number)" x-text="seat.number"></button>
                            <select
                                class="wm-seat-select"
                                x-bind:value="seatGuestKey(selectedTable(), seat.number)"
                                x-effect="$nextTick(() => $el.value = seatGuestKey(selectedTable(), seat.number))"
                                x-on:focus="selectSeat(selectedTable().id, seat.number)"
                                x-on:change="assignSeat(selectedTable().id, seat.number, $event.target.value)"
                            >
                                <option value="">Empty seat</option>
                                <template x-for="guest in availableGuestsForSeat(assignments(selectedTable())[seat.number])" :key="guest.key">
                                    <option :value="guest.key" :selected="guest.key === seatGuestKey(selectedTable(), seat.number)" x-text="guest.label"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </section>
            </template>

            <section class="wm-seat-panel">
                <h2>Guests</h2>
                <p class="wm-seat-muted"><span x-text="unassignedGuests().length"></span> guests without a seat</p>
                <div class="wm-seat-guest-list">
                    <template x-for="guest in guests" :key="guest.key">
                        <div class="wm-seat-guest-item" :class="{ 'is-assigned': assignedGuestKeys().includes(guest.key) }">
                            <div>
                                <p class="wm-seat-guest-name" x-text="guest.label"></p>
                                <p class="wm-seat-guest-group" x-text="guest.group"></p>
                            </div>
                            <span class="wm-seat-guest-status" x-show="assignedGuestKeys().includes(guest.key)">Seated</span>
                        </div>
                    </template>
                </div>
            </section>
        </aside>
    </div>

    @script
    <script>
        window.seatingAssigner = (config) => ({
                tables: config.tables || [],
                elements: config.elements || [],
                guests: config.guests || [],
                backgroundUrl: config.backgroundUrl,
                selectedId: (config.tables || [])[0]?.id || null,
                selectedSeatNumber: null,
                zoom: Number(config.viewport?.zoom || 1),
                viewportX: Number(config.viewport?.x || 0),
                viewportY: Number(config.viewport?.y || 0),
                dragState: null,
                saveTimer: null,
                saveStatus: 'Saved',
                gridColumns: Array.from({ length: 29 }, (_, index) => index * 50),
                gridRows: Array.from({ length: 19 }, (_, index) => index * 50),

                selectedTable() {
                    return this.tableById(this.selectedId);
                },
                tableById(id) {
                    return this.tables.find((table) => table.id === id) || null;
                },
                elementById(id) {
                    return this.elements.find((element) => element.id === id) || null;
                },
                elementTransform(id) {
                    const element = this.elementById(id);
                    if (! element) return 'translate(-9999, -9999)';

                    return `translate(${element.center_x}, ${element.center_y}) rotate(${element.rotation})`;
                },
                guestByKey(key) {
                    return this.guests.find((guest) => guest.key === key) || null;
                },
                viewportTransform() {
                    return `translate(${this.viewportX}, ${this.viewportY}) scale(${this.zoom})`;
                },
                selectTable(id) {
                    this.selectedId = id;
                    this.selectedSeatNumber = null;
                    this.assignments(this.selectedTable());
                },
                selectSeat(tableId, seatNumber) {
                    this.selectedId = tableId;
                    this.selectedSeatNumber = seatNumber;
                    this.$nextTick(() => {
                        document.getElementById(`seat-row-${tableId}-${seatNumber}`)?.scrollIntoView({ block: 'nearest' });
                    });
                },
                isActiveSeat(tableId, seatNumber) {
                    return this.selectedId === tableId && this.selectedSeatNumber === seatNumber;
                },
                isRound(table) {
                    return table && ['round', 'oval'].includes(table.table_type);
                },
                isChairRow(table) {
                    return table?.table_type === 'chair_row';
                },
                isLongTable(table) {
                    return table?.table_type === 'long_table';
                },
                isBoxTable(table) {
                    return table && ! this.isRound(table) && ! this.isLongTable(table) && ! this.isChairRow(table);
                },
                curveAmplitude(table, inflate = 0) {
                    if (Number(table?.curve_count || 0) === 0) {
                        return 0;
                    }

                    const width = Number(table?.secondary_dimension || 100) + (inflate * 2);
                    const factor = { subtle: 0.34, medium: 0.72, strong: 1.12 }[table?.curve_type || 'medium'] || 0.72;

                    return Math.max(14, width * factor);
                },
                longTableCurvePoint(table, progress, length = null, inflate = 0) {
                    length = length ?? Math.max(100, Number(table.primary_dimension || 800));
                    const curves = Math.max(0, Math.min(4, Number(table.curve_count || 0)));
                    const amplitude = this.curveAmplitude(table, inflate);
                    const x = -(length / 2) + (length * progress);
                    const angle = progress * Math.max(1, curves) * Math.PI;
                    const y = curves > 0 ? Math.sin(angle) * amplitude : 0;
                    const slope = curves > 0 ? Math.cos(angle) * amplitude * curves * Math.PI / length : 0;
                    const normalLength = Math.hypot(slope, 1);
                    const tangentLength = Math.hypot(1, slope);

                    return {
                        x,
                        y,
                        normalX: -slope / normalLength,
                        normalY: 1 / normalLength,
                        tangentX: 1 / tangentLength,
                        tangentY: slope / tangentLength,
                    };
                },
                longTablePath(table, inflate = 0) {
                    if (! table) return '';

                    const length = Math.max(100, Number(table.primary_dimension || 800)) + (inflate * 2);
                    const width = 100 + (inflate * 2);
                    const halfWidth = width / 2;
                    const curves = Math.max(0, Math.min(4, Number(table.curve_count || 0)));
                    const steps = Math.max(18, Math.max(1, curves) * 14);
                    const top = [];
                    const bottom = [];

                    for (let index = 0; index <= steps; index++) {
                        const point = this.longTableCurvePoint(table, index / steps, length, inflate);

                        top.push([point.x - (point.normalX * halfWidth), point.y - (point.normalY * halfWidth)]);
                        bottom.push([point.x + (point.normalX * halfWidth), point.y + (point.normalY * halfWidth)]);
                    }

                    const points = top.concat(bottom.reverse());

                    return points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point[0].toFixed(1)} ${point[1].toFixed(1)}`).join(' ') + ' Z';
                },
                seats(table) {
                    if (! table) return [];
                    const seats = [];
                    const width = Number(table.primary_dimension || 90);
                    const height = Number(table.secondary_dimension || width);
                    const seatGap = 18;
                    const chairInset = 7;

                    if (this.isChairRow(table)) {
                        const count = Number(table.seats_total || 0);
                        const spacing = 26;
                        const startX = -((count - 1) * spacing) / 2;
                        const curveType = table.curve_type || 'none';

                        if (curveType !== 'none' && count > 1) {
                            const chord = Math.max(spacing, (count - 1) * spacing);
                            const sagitta = curveType === 'high' ? 30 : 12;
                            const radius = ((chord * chord) / (8 * sagitta)) + (sagitta / 2);
                            const centerY = sagitta - radius;
                            const halfAngle = Math.asin(Math.min(0.98, (chord / 2) / radius));

                            for (let index = 0; index < count; index++) {
                                const ratio = index / (count - 1);
                                const angle = -halfAngle + (halfAngle * 2 * ratio);

                                seats.push({
                                    number: index + 1,
                                    x: Math.sin(angle) * radius,
                                    y: Math.cos(angle) * radius - radius + sagitta,
                                    rotation: (Math.atan2(centerY - (Math.cos(angle) * radius - radius + sagitta), -(Math.sin(angle) * radius)) * 180 / Math.PI) - 90,
                                });
                            }

                            return seats;
                        }

                        for (let index = 0; index < count; index++) {
                            seats.push({
                                number: index + 1,
                                x: startX + (index * spacing),
                                y: 0,
                                rotation: 180,
                            });
                        }

                        return seats;
                    }

                    if (this.isRound(table)) {
                        const count = Number(table.seats_total || 0);
                        const radiusX = (width / 2) + seatGap - chairInset;
                        const radiusY = (height / 2) + seatGap - chairInset;

                        for (let index = 0; index < count; index++) {
                            const angle = (Math.PI * 2 * index / Math.max(count, 1)) - (Math.PI / 2);
                            seats.push({
                                number: index + 1,
                                x: Math.cos(angle) * radiusX,
                                y: Math.sin(angle) * radiusY,
                                rotation: (angle * 180 / Math.PI) + 90,
                            });
                        }

                        return seats;
                    }

                    if (this.isLongTable(table)) {
                        const bySide = table.seats_by_side_json || { top: 0, right: 0, bottom: 0, left: 0 };
                        const length = Math.max(100, Number(table.primary_dimension || 800));
                        const halfWidth = 50;
                        const distance = halfWidth + seatGap - chairInset;
                        const rotationFor = (x, y) => (Math.atan2(y, x) * 180 / Math.PI) + 90;

                        ['top', 'bottom'].forEach((side) => {
                            const count = Number(bySide[side] || 0);

                            for (let index = 0; index < count; index++) {
                                const point = this.longTableCurvePoint(table, (index + 1) / (count + 1), length);
                                const direction = side === 'top' ? -1 : 1;
                                const outX = point.normalX * direction;
                                const outY = point.normalY * direction;

                                seats.push({
                                    number: seats.length + 1,
                                    x: point.x + (outX * distance),
                                    y: point.y + (outY * distance),
                                    rotation: rotationFor(outX, outY),
                                });
                            }
                        });

                        ['right', 'left'].forEach((side) => {
                            const count = Number(bySide[side] || 0);
                            const point = this.longTableCurvePoint(table, side === 'right' ? 1 : 0, length);
                            const outX = point.tangentX * (side === 'right' ? 1 : -1);
                            const outY = point.tangentY * (side === 'right' ? 1 : -1);

                            for (let index = 0; index < count; index++) {
                                const ratio = (index + 1) / (count + 1);
                                const offset = -halfWidth + (100 * ratio);

                                seats.push({
                                    number: seats.length + 1,
                                    x: point.x + (point.normalX * offset) + (outX * (seatGap - chairInset)),
                                    y: point.y + (point.normalY * offset) + (outY * (seatGap - chairInset)),
                                    rotation: rotationFor(outX, outY),
                                });
                            }
                        });

                        return seats;
                    }

                    const bySide = table.seats_by_side_json || { top: 0, right: 0, bottom: 0, left: 0 };
                    let number = 1;
                    const addSide = (side, count) => {
                        count = Number(count || 0);
                        for (let index = 0; index < count; index++) {
                            const ratio = (index + 1) / (count + 1);
                            if (side === 'top') seats.push({ number: number++, x: -width / 2 + width * ratio, y: -height / 2 - seatGap + chairInset, rotation: 0 });
                            if (side === 'right') seats.push({ number: number++, x: width / 2 + seatGap - chairInset, y: -height / 2 + height * ratio, rotation: 90 });
                            if (side === 'bottom') seats.push({ number: number++, x: -width / 2 + width * ratio, y: height / 2 + seatGap - chairInset, rotation: 180 });
                            if (side === 'left') seats.push({ number: number++, x: -width / 2 - seatGap + chairInset, y: -height / 2 + height * ratio, rotation: 270 });
                        }
                    };

                    addSide('top', bySide.top);
                    addSide('right', bySide.right);
                    addSide('bottom', bySide.bottom);
                    addSide('left', bySide.left);

                    return seats;
                },
                hasSeat(table, index) {
                    return this.seats(table).length > index;
                },
                seatAt(table, index) {
                    return this.seats(table)[index] || { x: 0, y: 0, rotation: 0, number: index + 1 };
                },
                seatTransform(table, index) {
                    const seat = this.seatAt(table, index);

                    return `translate(${seat.x}, ${seat.y}) rotate(${seat.rotation})`;
                },
                seatTagTransform(table, index) {
                    const seat = this.seatAt(table, index);
                    const isSideSeat = [90, 270].includes(((seat.rotation % 360) + 360) % 360);
                    const offset = isSideSeat ? 54 : 42;
                    const radians = (seat.rotation - 90) * Math.PI / 180;
                    const x = seat.x + Math.cos(radians) * offset;
                    const y = seat.y + Math.sin(radians) * offset;

                    return `translate(${x}, ${y})`;
                },
                seatTagWidth(table, seatNumber) {
                    return Math.max(62, Math.min(118, this.seatGuestShort(table, seatNumber).length * 7 + 18));
                },
                seatGuestKey(table, seatNumber) {
                    return this.assignments(table)[seatNumber] || '';
                },
                seatGuestShort(table, seatNumber) {
                    return this.guestByKey(this.seatGuestKey(table, seatNumber))?.short || '';
                },
                isSeatOccupied(table, index) {
                    return Boolean(this.seatGuestKey(table, index + 1));
                },
                assignedGuestKeys() {
                    return this.tables.flatMap((table) => Object.values(this.assignments(table)).filter(Boolean));
                },
                unassignedGuests() {
                    const assigned = this.assignedGuestKeys();

                    return this.guests.filter((guest) => ! assigned.includes(guest.key));
                },
                availableGuestsForSeat(currentKey) {
                    const assigned = this.assignedGuestKeys().filter((key) => key !== currentKey);

                    return this.guests.filter((guest) => ! assigned.includes(guest.key));
                },
                assignments(table) {
                    if (! table) return {};
                    if (! table.guest_assignments_json) {
                        table.guest_assignments_json = {};
                    }

                    if (Array.isArray(table.guest_assignments_json)) {
                        table.guest_assignments_json = table.guest_assignments_json.reduce((assignments, guestKey, index) => {
                            if (guestKey) {
                                assignments[index + 1] = guestKey;
                            }

                            return assignments;
                        }, {});
                    }

                    return table.guest_assignments_json;
                },
                assignSeat(tableId, seatNumber, guestKey) {
                    const table = this.tableById(tableId);
                    if (! table) return;

                    this.tables.forEach((item) => {
                        Object.keys(this.assignments(item)).forEach((key) => {
                            if (item.guest_assignments_json[key] === guestKey && guestKey) {
                                item.guest_assignments_json[key] = '';
                            }
                        });
                    });

                    if (guestKey) {
                        this.assignments(table)[seatNumber] = guestKey;
                    } else {
                        this.assignments(table)[seatNumber] = '';
                    }

                    this.tables = [...this.tables];
                    this.selectSeat(tableId, seatNumber);
                    this.scheduleSave();
                },
                tableAssignedCount(table) {
                    return Object.values(this.assignments(table)).filter(Boolean).length;
                },
                assignedCount() {
                    return this.tables.reduce((count, table) => count + this.tableAssignedCount(table), 0);
                },
                totalSeats() {
                    return this.tables.reduce((count, table) => count + this.seats(table).length, 0);
                },
                startPan(event) {
                    if (event.target.closest('.wm-seat-table')) return;

                    this.dragState = {
                        startX: event.clientX,
                        startY: event.clientY,
                        viewportX: this.viewportX,
                        viewportY: this.viewportY,
                    };
                    event.target.setPointerCapture?.(event.pointerId);
                },
                pointerMove(event) {
                    if (! this.dragState) return;

                    this.viewportX = Math.round(this.dragState.viewportX + event.clientX - this.dragState.startX);
                    this.viewportY = Math.round(this.dragState.viewportY + event.clientY - this.dragState.startY);
                },
                pointerUp() {
                    this.dragState = null;
                },
                zoomIn() {
                    this.changeZoom(0.1);
                },
                zoomOut() {
                    this.changeZoom(-0.1);
                },
                changeZoom(delta) {
                    this.zoom = Math.min(2.5, Math.max(0.35, Number((this.zoom + delta).toFixed(2))));
                },
                zoomWheel(event) {
                    const direction = event.deltaY < 0 ? 1 : -1;
                    this.changeZoom(direction * 0.03);
                },
                scheduleSave(delay = 500) {
                    clearTimeout(this.saveTimer);
                    this.saveStatus = 'Saving...';
                    this.saveTimer = setTimeout(() => this.save(), delay);
                },
                async save() {
                    await this.$wire.saveAssignments(JSON.parse(JSON.stringify(this.tables)));
                    this.saveStatus = 'Saved';
                },
            });
    </script>
    @endscript
</x-filament-panels::page>
