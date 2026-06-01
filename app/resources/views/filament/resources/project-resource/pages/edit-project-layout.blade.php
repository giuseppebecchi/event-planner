<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $plan = $this->currentSeatingPlan;
        $layoutsUrl = \App\Filament\Resources\ProjectResource::getUrl('layouts', ['record' => $record]);
        $initialTables = $this->getEditorTables();
        $initialElements = $this->getLayoutElements();
        $backgroundUrl = $this->getBackgroundImageUrl();
    @endphp

    <style>
        body:has(.wm-seat-editor) { overflow: hidden !important; }
        .fi-layout, .fi-main, .fi-page, .fi-page-content { overflow: hidden !important; }
        .fi-main { padding-inline: 0 !important; }
        .fi-page-content { padding: 0 !important; }
        .wm-seat-editor { position: fixed; inset: 0; z-index: 40; width: 100vw; height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) 22rem; background: #f3eee7; color: #2d2a26; overflow: hidden; }
        .wm-seat-stage { position: relative; display: grid; grid-template-rows: auto minmax(0, 1fr); min-width: 0; }
        .wm-seat-topbar { min-height: 3.8rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .65rem 1rem; background: rgba(255,255,255,.9); }
        .wm-seat-title { min-width: 0; }
        .wm-seat-title h1 { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .wm-seat-title p { margin: .18rem 0 0; color: #7c746c; font-size: .82rem; }
        .wm-seat-actions { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; justify-content: flex-end; }
        .wm-seat-button { min-height: 2.3rem; border: 1px solid #d8cab9; border-radius: .65rem; background: #fff; color: #504841; padding: 0 .75rem; font-weight: 800; font-size: .78rem; cursor: pointer; }
        .wm-seat-button.is-primary { border-color: #7a8f7b; background: #7a8f7b; color: #fff; }
        .wm-seat-button.is-tool { width: 2.3rem; padding: 0; }
        .wm-seat-add-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
        .wm-seat-add-button { min-height: 2.45rem; display: inline-flex; align-items: center; justify-content: center; gap: .38rem; border: 1px solid #d8cab9; border-radius: .65rem; background: #fff; color: #504841; padding: 0 .65rem; font-weight: 850; font-size: .78rem; cursor: pointer; }
        .wm-seat-add-button:hover { border-color: #c9a96a; background: #fffaf2; }
        .wm-seat-add-button.is-primary { border-color: #7a8f7b; background: #7a8f7b; color: #fff; }
        .wm-seat-add-button svg { width: .9rem; height: .9rem; stroke: currentColor; stroke-width: 2.6; fill: none; stroke-linecap: round; }
        .wm-seat-canvas-wrap { min-height: 0; padding: 1rem; overflow: hidden; }
        .wm-seat-canvas { width: 100%; height: 100%; border: 1px solid #d8cab9; border-radius: 1rem; background: #faf7f2; box-shadow: inset 0 0 0 1px rgba(255,255,255,.78), 0 18px 48px rgba(45,42,38,.08); touch-action: none; user-select: none; }
        .wm-seat-grid-line { stroke: rgba(121,112,102,.13); stroke-width: 1; }
        .wm-seat-table { cursor: grab; }
        .wm-seat-table.is-selected { cursor: grabbing; }
        .wm-seat-table-shape { fill: #fffdf9; stroke: #7a8f7b; stroke-width: 2.5; filter: drop-shadow(0 8px 10px rgba(45,42,38,.12)); }
        .wm-seat-table-glow { fill: #d9b86f; opacity: 0; filter: drop-shadow(0 0 18px rgba(201,169,106,.62)); pointer-events: none; }
        .wm-seat-table.is-selected .wm-seat-table-glow { opacity: .35; }
        .wm-seat-table.is-selected .wm-seat-table-shape { fill: #fff4d8; stroke: #c9a96a; stroke-width: 4; filter: drop-shadow(0 14px 18px rgba(201,169,106,.28)); }
        .wm-layout-element { cursor: grab; }
        .wm-layout-element.is-selected { cursor: grabbing; }
        .wm-layout-element-shape { stroke: #a88f62; stroke-width: 2; stroke-dasharray: 6 4; filter: drop-shadow(0 8px 10px rgba(45,42,38,.08)); }
        .wm-layout-element.is-selected .wm-layout-element-shape { stroke: #c9a96a; stroke-width: 3.5; }
        .wm-seat-hit-area { fill: transparent; stroke: transparent; pointer-events: all; }
        .wm-seat-label { fill: #2d2a26; font-size: 13px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; pointer-events: none; }
        .wm-layout-label { fill: #4b433b; font-size: 15px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; pointer-events: none; }
        .wm-seat-chair { pointer-events: none; }
        .wm-seat-chair-seat { fill: #fffaf2; stroke: #9d8451; stroke-width: 1.4; }
        .wm-seat-chair-back { fill: #d8c298; stroke: #9d8451; stroke-width: 1.4; }
        .wm-seat-rotate-handle { cursor: alias; }
        .wm-seat-rotate-handle-bg { fill: #fffaf2; stroke: #c9a96a; stroke-width: 2.4; filter: drop-shadow(0 6px 8px rgba(45,42,38,.16)); }
        .wm-seat-rotate-handle-ring { fill: none; stroke: rgba(201,169,106,.25); stroke-width: 2; }
        .wm-seat-rotate-handle-icon { fill: none; stroke: #8b6f37; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
        .wm-seat-rotate-handle-tip { fill: #8b6f37; stroke: none; }
        .wm-seat-sidebar { min-width: 0; height: 100vh; border-left: 1px solid #ded3c7; background: rgba(255,255,255,.94); overflow-y: auto; overscroll-behavior: contain; }
        .wm-seat-panel { padding: 1rem; border-bottom: 1px solid #ece3d9; display: grid; gap: .8rem; }
        .wm-seat-panel h2 { margin: 0; color: #2d2a26; font-size: .78rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
        .wm-seat-field { display: grid; gap: .35rem; }
        .wm-seat-field label { color: #6c645d; font-size: .7rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .wm-seat-input, .wm-seat-select { width: 100%; min-height: 2.45rem; border: 1px solid #ddd2c5; border-radius: .7rem; background: #fff; color: #2d2a26; padding: 0 .72rem; }
        .wm-seat-file { width: 100%; font-size: .8rem; }
        .wm-seat-prop-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
        .wm-seat-info-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .wm-seat-icon-actions { display: flex; align-items: center; gap: .45rem; }
        .wm-seat-icon-button { width: 2.25rem; height: 2.25rem; display: inline-grid; place-items: center; border: 1px solid #d8cab9; border-radius: .65rem; background: #fffaf2; color: #6f5d3f; cursor: pointer; }
        .wm-seat-icon-button:hover { border-color: #c9a96a; color: #8b6f37; }
        .wm-seat-icon-button.is-danger { color: #9d4b3f; }
        .wm-seat-icon-button svg { width: 1.05rem; height: 1.05rem; stroke: currentColor; stroke-width: 2.2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
        .wm-seat-muted { margin: 0; color: #8b8279; line-height: 1.55; font-size: .88rem; }
        .wm-seat-info-value { margin: 0; color: #2d2a26; font-size: 1rem; font-weight: 900; }
        .wm-seat-save-state { color: #8b8279; font-size: .78rem; font-weight: 800; }
        .wm-seat-modal-backdrop { position: fixed; inset: 0; z-index: 50; background: rgba(31,25,20,.34); }
        .wm-seat-modal { position: fixed; top: 50%; left: 50%; z-index: 60; width: min(32rem, calc(100vw - 2rem)); transform: translate(-50%, -50%); border: 1px solid #d9ccc0; border-radius: 1rem; background: #fff; box-shadow: 0 24px 60px rgba(24,18,14,.2); padding: 1rem; display: grid; gap: 1rem; }
        .wm-seat-modal-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .wm-seat-modal-head h2 { margin: 0; color: #2d2a26; font-size: .9rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .wm-seat-modal-actions { display: flex; justify-content: flex-end; gap: .65rem; }
        [x-cloak] { display: none !important; }
        @media (max-width: 980px) {
            .wm-seat-editor { grid-template-columns: minmax(0, 1fr); overflow: hidden; }
            .wm-seat-canvas-wrap { height: 70vh; }
            .wm-seat-sidebar { height: 30vh; border-left: 0; border-top: 1px solid #ded3c7; }
        }
    </style>

    <div
        class="wm-seat-editor"
        x-data="seatingEditor({
            planId: @js($plan->id),
            tables: @js($initialTables),
            elements: @js($initialElements),
            backgroundUrl: @js($backgroundUrl),
            viewport: @js($this->getViewportState()),
        })"
        x-on:keydown.window="handleKeydown($event)"
        x-on:seating-background-updated.window="backgroundUrl = $event.detail.url ?? $event.detail[0]?.url ?? null"
    >
        <section class="wm-seat-stage">
            <header class="wm-seat-topbar">
                <div class="wm-seat-title">
                    <h1>{{ $plan->name }}</h1>
                    <p>{{ \App\Models\ProjectSeatingPlan::PLAN_TYPE_OPTIONS[$plan->plan_type] ?? ($plan->plan_type ?: 'Layout') }} · <span x-text="tables.length"></span> seating items</p>
                </div>
                <div class="wm-seat-actions">
                    <a class="wm-seat-button" href="{{ $layoutsUrl }}">Back</a>
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

                        <image
                            x-show="backgroundUrl"
                            :href="backgroundUrl"
                            x="0"
                            y="0"
                            width="1400"
                            height="900"
                            preserveAspectRatio="xMidYMid slice"
                            opacity=".74"
                        ></image>

                        @foreach ($initialElements as $element)
                            <g
                                class="wm-layout-element"
                                transform="translate({{ $element['center_x'] }}, {{ $element['center_y'] }}) rotate({{ $element['rotation'] }})"
                                x-bind:class="{ 'is-selected': selectedType === 'element' && selectedId === {{ $element['id'] }} }"
                                x-bind:transform="elementTransform({{ $element['id'] }})"
                                x-on:pointerdown.stop="startDrag($event, elementById({{ $element['id'] }}), 'element')"
                                x-on:click.stop="selectElement({{ $element['id'] }})"
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
                                <rect
                                    class="wm-seat-hit-area"
                                    x="{{ -$element['width'] / 2 - 8 }}"
                                    y="{{ -$element['height'] / 2 - 8 }}"
                                    width="{{ $element['width'] + 16 }}"
                                    height="{{ $element['height'] + 16 }}"
                                    x-bind:x="-elementById({{ $element['id'] }}).width / 2 - 8"
                                    x-bind:y="-elementById({{ $element['id'] }}).height / 2 - 8"
                                    x-bind:width="elementById({{ $element['id'] }}).width + 16"
                                    x-bind:height="elementById({{ $element['id'] }}).height + 16"
                                ></rect>
                                <text class="wm-layout-label" x="0" y="0" x-text="elementById({{ $element['id'] }}).label">{{ $element['label'] }}</text>
                                <g
                                    x-show="selectedType === 'element' && selectedId === {{ $element['id'] }}"
                                    class="wm-seat-rotate-handle"
                                    transform="translate(0, {{ -($element['height'] / 2 + 50) }})"
                                    x-bind:transform="`translate(0, ${-(elementById({{ $element['id'] }}).height / 2 + 50)})`"
                                    x-on:pointerdown.stop="startRotate($event, elementById({{ $element['id'] }}), 'element')"
                                >
                                    <circle class="wm-seat-rotate-handle-bg" cx="0" cy="0" r="15"></circle>
                                    <circle class="wm-seat-rotate-handle-ring" cx="0" cy="0" r="9"></circle>
                                    <path class="wm-seat-rotate-handle-icon" d="M -5.5 4.5 A 8 8 0 1 1 5.5 4.5"></path>
                                    <path class="wm-seat-rotate-handle-tip" d="M 5.5 4.5 L 11 3.5 L 7.8 8.2 Z"></path>
                                </g>
                            </g>
                        @endforeach

                        @foreach ($initialTables as $table)
                            <g
                                class="wm-seat-table"
                                transform="translate({{ $table['center_x'] }}, {{ $table['center_y'] }}) rotate({{ $table['rotation'] }})"
                                x-bind:class="{ 'is-selected': selectedType === 'table' && selectedId === {{ $table['id'] }} }"
                                x-bind:transform="tableTransform({{ $table['id'] }})"
                                x-on:pointerdown.stop="startDrag($event, tableById({{ $table['id'] }}))"
                                x-on:click.stop="select({{ $table['id'] }})"
                            >
                                <rect
                                    class="wm-seat-hit-area"
                                    x="{{ -($table['primary_dimension'] / 2) - 18 }}"
                                    y="-28"
                                    width="{{ $table['primary_dimension'] + 36 }}"
                                    height="74"
                                    rx="18"
                                    x-show="isChairRow(tableById({{ $table['id'] }}))"
                                    x-bind:x="-(tableById({{ $table['id'] }}).primary_dimension / 2) - 18"
                                    x-bind:width="tableById({{ $table['id'] }}).primary_dimension + 36"
                                    @if ($table['table_type'] !== 'chair_row') style="display: none;" @endif
                                ></rect>
                                <ellipse
                                    class="wm-seat-table-glow"
                                    cx="0"
                                    cy="0"
                                    rx="{{ ($table['primary_dimension'] / 2) + 18 }}"
                                    ry="{{ ($table['secondary_dimension'] / 2) + 18 }}"
                                    x-show="isRound(tableById({{ $table['id'] }}))"
                                    x-bind:rx="(tableById({{ $table['id'] }}).primary_dimension / 2) + 18"
                                    x-bind:ry="(tableById({{ $table['id'] }}).secondary_dimension / 2) + 18"
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
                                    x-bind:x="-(tableById({{ $table['id'] }}).primary_dimension / 2) - 18"
                                    x-bind:y="-(tableById({{ $table['id'] }}).secondary_dimension / 2) - 18"
                                    x-bind:width="tableById({{ $table['id'] }}).primary_dimension + 36"
                                    x-bind:height="tableById({{ $table['id'] }}).secondary_dimension + 36"
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
                                    x-bind:rx="tableById({{ $table['id'] }}).primary_dimension / 2"
                                    x-bind:ry="tableById({{ $table['id'] }}).secondary_dimension / 2"
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
                                    x-bind:x="-tableById({{ $table['id'] }}).primary_dimension / 2"
                                    x-bind:y="-tableById({{ $table['id'] }}).secondary_dimension / 2"
                                    x-bind:width="tableById({{ $table['id'] }}).primary_dimension"
                                    x-bind:height="tableById({{ $table['id'] }}).secondary_dimension"
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
                                        x-bind:transform="seatTransform(tableById({{ $table['id'] }}), {{ $seatIndex }})"
                                        style="display: none;"
                                    >
                                        <rect class="wm-seat-chair-seat" x="-8" y="-6" width="16" height="14" rx="4"></rect>
                                        <rect class="wm-seat-chair-back" x="-10" y="-14" width="20" height="7" rx="3"></rect>
                                        <line class="wm-seat-chair-seat" x1="-6" y1="9" x2="-6" y2="13"></line>
                                        <line class="wm-seat-chair-seat" x1="6" y1="9" x2="6" y2="13"></line>
                                    </g>
                                @endfor

                                <text
                                    class="wm-seat-label"
                                    x="0"
                                    y="0"
                                    x-bind:y="isChairRow(tableById({{ $table['id'] }})) ? 34 : 0"
                                    x-text="tableById({{ $table['id'] }}).name"
                                >{{ $table['name'] }}</text>

                                <g
                                    x-show="selectedType === 'table' && selectedId === {{ $table['id'] }}"
                                    class="wm-seat-rotate-handle"
                                    transform="translate(0, {{ -($table['secondary_dimension'] / 2 + 50) }})"
                                    x-bind:transform="`translate(0, ${-(tableById({{ $table['id'] }}).secondary_dimension / 2 + 50)})`"
                                    x-on:pointerdown.stop="startRotate($event, tableById({{ $table['id'] }}))"
                                >
                                    <circle class="wm-seat-rotate-handle-bg" cx="0" cy="0" r="15"></circle>
                                    <circle class="wm-seat-rotate-handle-ring" cx="0" cy="0" r="9"></circle>
                                    <path class="wm-seat-rotate-handle-icon" d="M -5.5 4.5 A 8 8 0 1 1 5.5 4.5"></path>
                                    <path class="wm-seat-rotate-handle-tip" d="M 5.5 4.5 L 11 3.5 L 7.8 8.2 Z"></path>
                                </g>
                            </g>
                        @endforeach
                    </g>
                </svg>
            </div>
        </section>

        <aside class="wm-seat-sidebar">
            <section class="wm-seat-panel">
                <h2>Background</h2>
                <input class="wm-seat-file" type="file" wire:model.live="backgroundUpload" accept="image/*">
                @error('backgroundUpload')
                    <p class="wm-seat-muted">{{ $message }}</p>
                @enderror
                <div class="wm-seat-actions">
                    <span class="wm-seat-save-state" wire:loading wire:target="backgroundUpload">Uploading...</span>
                    @if ($backgroundUrl)
                        <button type="button" class="wm-seat-button" wire:click="removeBackgroundImage" x-on:click="backgroundUrl = null">
                            Remove
                        </button>
                    @endif
                </div>
            </section>

            <section class="wm-seat-panel">
                <h2>Tables and Seatings</h2>
                <div class="wm-seat-add-grid">
                    <button type="button" class="wm-seat-add-button is-primary" x-on:click="openAddTablesModal('round')">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Tables
                    </button>
                    <button type="button" class="wm-seat-add-button" x-on:click="openAddTablesModal('chair_row')">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Seatings
                    </button>
                </div>
                <p class="wm-seat-muted">Add tables or rows of seats to the plan.</p>
            </section>

            <section class="wm-seat-panel">
                <h2>Layout elements</h2>
                <div class="wm-seat-add-grid">
                    <button type="button" class="wm-seat-add-button" x-on:click="addLayoutElement('text')">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Text
                    </button>
                    <button type="button" class="wm-seat-add-button" x-on:click="addLayoutElement('space', 'rectangle')">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Rectangle
                    </button>
                    <button type="button" class="wm-seat-add-button" x-on:click="addLayoutElement('space', 'circle')">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Circle
                    </button>
                </div>
                <p class="wm-seat-muted">Add labels or fixed areas to describe the layout.</p>
            </section>

            <template x-if="selectedTable()">
                <section class="wm-seat-panel">
                    <h2>Seating info</h2>
                    <div class="wm-seat-info-head">
                        <p class="wm-seat-info-value" x-text="selectedTable().name"></p>
                        <div class="wm-seat-icon-actions" aria-label="Table actions">
                            <button type="button" class="wm-seat-icon-button" title="Duplicate seating item" x-on:click="duplicateSelectedTable">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="8" y="8" width="11" height="11" rx="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1"></path>
                                    <path d="M13.5 11.5v4"></path>
                                    <path d="M11.5 13.5h4"></path>
                                </svg>
                            </button>
                            <button type="button" class="wm-seat-icon-button is-danger" title="Delete seating item" x-on:click="openDeleteTableModal">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 6h18"></path>
                                    <path d="M8 6V4h8v2"></path>
                                    <path d="M19 6l-1 14H6L5 6"></path>
                                    <path d="M10 11v5"></path>
                                    <path d="M14 11v5"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="wm-seat-field">
                        <label>Name</label>
                        <input class="wm-seat-input" x-model="selectedTable().name" x-on:input.debounce.700ms="scheduleSave()">
                    </div>
                    <div class="wm-seat-field">
                        <label>Type</label>
                        <select class="wm-seat-select" x-model="selectedTable().table_type" x-on:change="normalizeSelectedSeats(); scheduleSave()">
                            <option value="round">Round</option>
                            <option value="oval">Oval</option>
                            <option value="square">Square</option>
                            <option value="rectangular">Rectangular</option>
                            <option value="long_table">Long table</option>
                            <option value="chair_row">Chair row</option>
                        </select>
                    </div>
                    <div class="wm-seat-prop-grid" x-show="! isChairRow(selectedTable()) && ! isLongTable(selectedTable())">
                        <div class="wm-seat-field">
                            <label>Width</label>
                            <input class="wm-seat-input" type="number" min="20" max="2000" x-model.number="selectedTable().primary_dimension" x-on:input.debounce.700ms="scheduleSave()">
                        </div>
                        <div class="wm-seat-field">
                            <label>Height</label>
                            <input class="wm-seat-input" type="number" min="20" max="2000" x-model.number="selectedTable().secondary_dimension" x-on:input.debounce.700ms="scheduleSave()">
                        </div>
                        <template x-if="isRound(selectedTable())">
                            <div class="wm-seat-field">
                                <label>Seats</label>
                                <input class="wm-seat-input" type="number" min="0" max="80" x-model.number="selectedTable().seats_total" x-on:input.debounce.700ms="scheduleSave()">
                            </div>
                        </template>
                    </div>

                    <template x-if="isLongTable(selectedTable())">
                        <div class="wm-seat-prop-grid">
                            <div class="wm-seat-field">
                                <label>Length</label>
                                <input class="wm-seat-input" type="number" min="100" max="2000" x-model.number="selectedTable().primary_dimension" x-on:input.debounce.700ms="normalizeSelectedSeats(); scheduleSave()">
                            </div>
                            <div class="wm-seat-field">
                                <label>Curves</label>
                                <select class="wm-seat-select" x-model.number="selectedTable().curve_count" x-on:change="scheduleSave()">
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                            </div>
                            <div class="wm-seat-field" x-show="Number(selectedTable().curve_count || 0) > 0">
                                <label>Curve type</label>
                                <select class="wm-seat-select" x-model="selectedTable().curve_type" x-on:change="scheduleSave()">
                                    <option value="subtle">Subtle</option>
                                    <option value="medium">Medium</option>
                                    <option value="strong">Pronounced</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <template x-if="isChairRow(selectedTable())">
                        <div class="wm-seat-prop-grid">
                            <div class="wm-seat-field">
                                <label>Seats</label>
                                <input class="wm-seat-input" type="number" min="0" max="80" x-model.number="selectedTable().seats_total" x-on:input.debounce.700ms="normalizeSelectedSeats(); scheduleSave()">
                            </div>
                            <div class="wm-seat-field">
                                <label>Curve type</label>
                                <select class="wm-seat-select" x-model="selectedTable().curve_type" x-on:change="normalizeSelectedSeats(); scheduleSave()">
                                    <option value="none">None</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <template x-if="! isRound(selectedTable()) && ! isChairRow(selectedTable())">
                        <div class="wm-seat-prop-grid">
                            <div class="wm-seat-field">
                                <label>Top seats</label>
                                <input class="wm-seat-input" type="number" min="0" max="40" x-model.number="selectedTable().seats_by_side_json.top" x-on:input.debounce.700ms="scheduleSave()">
                            </div>
                            <div class="wm-seat-field">
                                <label>Right seats</label>
                                <input class="wm-seat-input" type="number" min="0" max="40" x-model.number="selectedTable().seats_by_side_json.right" x-on:input.debounce.700ms="scheduleSave()">
                            </div>
                            <div class="wm-seat-field">
                                <label>Bottom seats</label>
                                <input class="wm-seat-input" type="number" min="0" max="40" x-model.number="selectedTable().seats_by_side_json.bottom" x-on:input.debounce.700ms="scheduleSave()">
                            </div>
                            <div class="wm-seat-field">
                                <label>Left seats</label>
                                <input class="wm-seat-input" type="number" min="0" max="40" x-model.number="selectedTable().seats_by_side_json.left" x-on:input.debounce.700ms="scheduleSave()">
                            </div>
                        </div>
                    </template>
                </section>
            </template>

            <template x-if="selectedElement()">
                <section class="wm-seat-panel">
                    <h2>Layout element</h2>
                    <div class="wm-seat-info-head">
                        <p class="wm-seat-info-value" x-text="selectedElement().label || (selectedElement().element_type === 'text' ? 'Text' : 'Space')"></p>
                        <button type="button" class="wm-seat-icon-button is-danger" title="Delete layout element" x-on:click="openDeleteElementModal">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M19 6l-1 14H6L5 6"></path>
                                <path d="M10 11v5"></path>
                                <path d="M14 11v5"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="wm-seat-field">
                        <label>Label</label>
                        <input class="wm-seat-input" x-model="selectedElement().label" x-on:input.debounce.700ms="scheduleSave()">
                    </div>
                    <template x-if="selectedElement().element_type === 'space'">
                        <div class="wm-seat-field">
                            <label>Shape</label>
                            <select class="wm-seat-select" x-model="selectedElement().shape" x-on:change="normalizeSelectedElement(); scheduleSave()">
                                <option value="rectangle">Rectangle</option>
                                <option value="circle">Circle</option>
                            </select>
                        </div>
                    </template>
                    <div class="wm-seat-prop-grid">
                        <div class="wm-seat-field">
                            <label>Width</label>
                            <input class="wm-seat-input" type="number" min="20" max="2000" x-model.number="selectedElement().width" x-on:input.debounce.700ms="normalizeSelectedElement(); scheduleSave()">
                        </div>
                        <div class="wm-seat-field">
                            <label>Height</label>
                            <input class="wm-seat-input" type="number" min="20" max="2000" x-model.number="selectedElement().height" x-on:input.debounce.700ms="normalizeSelectedElement(); scheduleSave()">
                        </div>
                    </div>
                    <div class="wm-seat-field" x-show="selectedElement().element_type === 'space'">
                        <label>Background</label>
                        <input class="wm-seat-input" type="color" x-model="selectedElement().background_color" x-on:input.debounce.500ms="scheduleSave()">
                    </div>
                </section>
            </template>
        </aside>

        <template x-if="showAddTablesModal">
            <div>
                <div class="wm-seat-modal-backdrop" x-on:click="showAddTablesModal = false"></div>
                <div class="wm-seat-modal" role="dialog" aria-modal="true" x-cloak>
                    <div class="wm-seat-modal-head">
                        <h2>Add table / seating</h2>
                        <button type="button" class="wm-seat-button is-tool" x-on:click="showAddTablesModal = false">x</button>
                    </div>
                    <div class="wm-seat-field">
                        <label>Type</label>
                        <select class="wm-seat-select" x-model="addForm.type">
                            <option value="round">Round tables</option>
                            <option value="rectangular">Rectangular tables</option>
                            <option value="long_table">Long tables</option>
                            <option value="chair_row">Chair rows</option>
                        </select>
                    </div>
                    <div class="wm-seat-prop-grid">
                        <div class="wm-seat-field">
                            <label>How many</label>
                            <input class="wm-seat-input" type="number" min="1" max="80" x-model.number="addForm.count">
                        </div>
                        <div class="wm-seat-field">
                            <label>Seats each</label>
                            <input class="wm-seat-input" type="number" min="0" max="80" x-model.number="addForm.seats">
                        </div>
                    </div>
                    <div class="wm-seat-modal-actions">
                        <button type="button" class="wm-seat-button" x-on:click="showAddTablesModal = false">Cancel</button>
                        <button type="button" class="wm-seat-button is-primary" x-on:click="addTablesFromModal">Add</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="showDeleteTableModal">
            <div>
                <div class="wm-seat-modal-backdrop" x-on:click="showDeleteTableModal = false"></div>
                <div class="wm-seat-modal" role="dialog" aria-modal="true" x-cloak>
                    <div class="wm-seat-modal-head">
                        <h2>Delete seating item</h2>
                        <button type="button" class="wm-seat-button is-tool" x-on:click="showDeleteTableModal = false">x</button>
                    </div>
                    <p class="wm-seat-muted">
                        Delete <strong x-text="selectedTable()?.name"></strong>? This action cannot be undone.
                    </p>
                    <div class="wm-seat-modal-actions">
                        <button type="button" class="wm-seat-button" x-on:click="showDeleteTableModal = false">Cancel</button>
                        <button type="button" class="wm-seat-button is-primary" x-on:click="deleteSelectedTable">Delete</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="showDeleteElementModal">
            <div>
                <div class="wm-seat-modal-backdrop" x-on:click="showDeleteElementModal = false"></div>
                <div class="wm-seat-modal" role="dialog" aria-modal="true" x-cloak>
                    <div class="wm-seat-modal-head">
                        <h2>Delete layout element</h2>
                        <button type="button" class="wm-seat-button is-tool" x-on:click="showDeleteElementModal = false">x</button>
                    </div>
                    <p class="wm-seat-muted">
                        Delete <strong x-text="selectedElement()?.label || 'element'"></strong>? This action cannot be undone.
                    </p>
                    <div class="wm-seat-modal-actions">
                        <button type="button" class="wm-seat-button" x-on:click="showDeleteElementModal = false">Cancel</button>
                        <button type="button" class="wm-seat-button is-primary" x-on:click="deleteSelectedElement">Delete</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @script
    <script>
        window.seatingEditor = (config) => ({
                planId: config.planId,
                tables: config.tables || [],
                elements: config.elements || [],
                backgroundUrl: config.backgroundUrl,
                selectedId: null,
                selectedType: null,
                zoom: Number(config.viewport?.zoom || 1),
                viewportX: Number(config.viewport?.x || 0),
                viewportY: Number(config.viewport?.y || 0),
                dragState: null,
                saveTimer: null,
                viewportSaveTimer: null,
                previewSaveTimer: null,
                saveStatus: 'Saved',
                showAddTablesModal: false,
                showDeleteTableModal: false,
                showDeleteElementModal: false,
                addForm: { type: 'round', count: 1, seats: 8 },
                canvasWidth: 1400,
                canvasHeight: 900,
                gridColumns: Array.from({ length: 29 }, (_, index) => index * 50),
                gridRows: Array.from({ length: 19 }, (_, index) => index * 50),

                init() {
                    const pendingSelection = window.localStorage.getItem(this.selectionStorageKey());
                    const pendingElementSelection = window.localStorage.getItem(`${this.selectionStorageKey()}-element`);

                    if (pendingSelection && this.tableById(Number(pendingSelection))) {
                        this.selectedId = Number(pendingSelection);
                        this.selectedType = 'table';
                    }

                    if (pendingElementSelection && this.elementById(Number(pendingElementSelection))) {
                        this.selectedId = Number(pendingElementSelection);
                        this.selectedType = 'element';
                    }

                    window.localStorage.removeItem(this.selectionStorageKey());
                    window.localStorage.removeItem(`${this.selectionStorageKey()}-element`);

                    window.addEventListener('seating-background-updated', (event) => {
                        this.backgroundUrl = event.detail.url ?? event.detail[0]?.url ?? null;
                        this.schedulePreviewSave(500);
                    });
                    this.$nextTick(() => this.schedulePreviewSave(1200));
                },

                selectedTable() {
                    if (this.selectedType !== 'table') return null;

                    return this.tables.find((table) => table.id === this.selectedId) || null;
                },
                selectedElement() {
                    if (this.selectedType !== 'element') return null;

                    return this.elements.find((element) => element.id === this.selectedId) || null;
                },
                tableById(id) {
                    return this.tables.find((table) => table.id === id) || null;
                },
                elementById(id) {
                    return this.elements.find((element) => element.id === id) || null;
                },
                selectionStorageKey() {
                    return `seating-editor-selected-${this.planId}`;
                },
                tableTransform(id) {
                    const table = this.tableById(id);
                    if (! table) return 'translate(-9999, -9999)';

                    return `translate(${table.center_x}, ${table.center_y}) rotate(${table.rotation})`;
                },
                elementTransform(id) {
                    const element = this.elementById(id);
                    if (! element) return 'translate(-9999, -9999)';

                    return `translate(${element.center_x}, ${element.center_y}) rotate(${element.rotation})`;
                },
                viewportTransform() {
                    return `translate(${this.viewportX}, ${this.viewportY}) scale(${this.zoom})`;
                },
                select(id) {
                    this.selectedId = id;
                    this.selectedType = 'table';
                    this.normalizeSelectedSeats();
                },
                selectElement(id) {
                    this.selectedId = id;
                    this.selectedType = 'element';
                    this.normalizeSelectedElement();
                },
                clearSelection() {
                    this.selectedId = null;
                    this.selectedType = null;
                },
                handleKeydown(event) {
                    const target = event.target;
                    const isEditing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(target?.tagName) || target?.isContentEditable;

                    if (isEditing || ! ['Delete', 'Backspace'].includes(event.key) || (! this.selectedTable() && ! this.selectedElement())) {
                        return;
                    }

                    event.preventDefault();
                    if (this.selectedElement()) {
                        this.openDeleteElementModal();
                    } else {
                        this.openDeleteTableModal();
                    }
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
                chairRowWidth(seats) {
                    return Math.max(44, (Number(seats || 0) * 26) + 40);
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
                normalizeSelectedSeats() {
                    const table = this.selectedTable();
                    if (! table) return;

                    if (this.isChairRow(table)) {
                        table.seats_total = Number(table.seats_total || 8);
                        table.primary_dimension = this.chairRowWidth(table.seats_total);
                        table.secondary_dimension = 24;
                        table.curve_type = ['none', 'medium', 'high'].includes(table.curve_type) ? table.curve_type : 'none';
                        table.seats_by_side_json = table.seats_by_side_json || { top: 0, right: 0, bottom: 0, left: 0 };
                    } else if (this.isRound(table)) {
                        table.seats_total = Number(table.seats_total || 8);
                        table.seats_by_side_json = table.seats_by_side_json || { top: 0, right: 0, bottom: 0, left: 0 };
                    } else if (this.isLongTable(table)) {
                        table.seats_total = null;
                        table.primary_dimension = Math.max(100, Number(table.primary_dimension || 800));
                        table.secondary_dimension = 100;
                        table.curve_count = Math.max(0, Math.min(4, Number(table.curve_count || 0)));
                        table.curve_type = ['subtle', 'medium', 'strong'].includes(table.curve_type) ? table.curve_type : 'medium';
                        table.seats_by_side_json = table.seats_by_side_json || { top: 8, right: 1, bottom: 8, left: 1 };
                        if (Number(table.seats_by_side_json.top || 0) + Number(table.seats_by_side_json.right || 0) + Number(table.seats_by_side_json.bottom || 0) + Number(table.seats_by_side_json.left || 0) === 0) {
                            table.seats_by_side_json = { top: 8, right: 1, bottom: 8, left: 1 };
                        }
                        table.seats_by_side_json.top = Number(table.seats_by_side_json.top ?? 8);
                        table.seats_by_side_json.right = Number(table.seats_by_side_json.right ?? 1);
                        table.seats_by_side_json.bottom = Number(table.seats_by_side_json.bottom ?? 8);
                        table.seats_by_side_json.left = Number(table.seats_by_side_json.left ?? 1);
                    } else {
                        table.seats_total = null;
                        table.seats_by_side_json = table.seats_by_side_json || { top: 2, right: 2, bottom: 2, left: 2 };
                        table.seats_by_side_json.top = Number(table.seats_by_side_json.top || 0);
                        table.seats_by_side_json.right = Number(table.seats_by_side_json.right || 0);
                        table.seats_by_side_json.bottom = Number(table.seats_by_side_json.bottom || 0);
                        table.seats_by_side_json.left = Number(table.seats_by_side_json.left || 0);
                    }
                },
                normalizeSelectedElement() {
                    const element = this.selectedElement();
                    if (! element) return;

                    element.width = Math.max(20, Number(element.width || 120));
                    element.height = element.shape === 'circle'
                        ? element.width
                        : Math.max(20, Number(element.height || 80));
                    element.background_color = element.background_color && element.background_color !== 'transparent'
                        ? element.background_color
                        : '#f3eadc';
                    element.label = element.label ?? '';
                },
                point(event) {
                    const point = this.$refs.svg.createSVGPoint();
                    point.x = event.clientX;
                    point.y = event.clientY;
                    return point.matrixTransform(this.$refs.viewport.getScreenCTM().inverse());
                },
                startDrag(event, item, type = 'table') {
                    if (! item) return;
                    if (type === 'element') {
                        this.selectElement(item.id);
                    } else {
                        this.select(item.id);
                    }
                    const point = this.point(event);
                    this.dragState = {
                        mode: 'drag',
                        type,
                        id: item.id,
                        offsetX: point.x - item.center_x,
                        offsetY: point.y - item.center_y,
                    };
                    event.target.setPointerCapture?.(event.pointerId);
                },
                startRotate(event, item, type = 'table') {
                    if (! item) return;
                    if (type === 'element') {
                        this.selectElement(item.id);
                    } else {
                        this.select(item.id);
                    }
                    this.dragState = { mode: 'rotate', type, id: item.id };
                    event.target.setPointerCapture?.(event.pointerId);
                },
                startPan(event) {
                    if (event.target.closest('.wm-seat-table, .wm-layout-element')) return;

                    this.clearSelection();
                    this.dragState = {
                        mode: 'pan',
                        startX: event.clientX,
                        startY: event.clientY,
                        viewportX: this.viewportX,
                        viewportY: this.viewportY,
                    };
                    event.target.setPointerCapture?.(event.pointerId);
                },
                pointerMove(event) {
                    if (! this.dragState) return;
                    if (this.dragState.mode === 'pan') {
                        this.viewportX = Math.round(this.dragState.viewportX + event.clientX - this.dragState.startX);
                        this.viewportY = Math.round(this.dragState.viewportY + event.clientY - this.dragState.startY);
                        return;
                    }

                    const item = this.dragState.type === 'element'
                        ? this.elements.find((element) => element.id === this.dragState.id)
                        : this.tables.find((table) => table.id === this.dragState.id);
                    if (! item) return;
                    const point = this.point(event);

                    if (this.dragState.mode === 'drag') {
                        item.center_x = Math.round(point.x - this.dragState.offsetX);
                        item.center_y = Math.round(point.y - this.dragState.offsetY);
                        return;
                    }

                    const radians = Math.atan2(point.y - item.center_y, point.x - item.center_x);
                    item.rotation = Math.round((radians * 180 / Math.PI) + 90);
                },
                pointerUp() {
                    if (this.dragState?.mode === 'drag' || this.dragState?.mode === 'rotate') {
                        this.scheduleSave(0);
                    }

                    if (this.dragState?.mode === 'pan') {
                        this.scheduleViewportSave(0);
                    }

                    this.dragState = null;
                },
                seats(table) {
                    if (! table) return [];
                    const seats = [];
                    const width = Number(table.primary_dimension || 90);
                    const height = Number(table.secondary_dimension || width);
                    const seatGap = 18;
                    const chairInset = 7;

                    if (this.isChairRow(table)) {
                        table.primary_dimension = this.chairRowWidth(table.seats_total);
                        table.secondary_dimension = 24;
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
                                    x: Math.sin(angle) * radius,
                                    y: Math.cos(angle) * radius - radius + sagitta,
                                    rotation: (Math.atan2(centerY - (Math.cos(angle) * radius - radius + sagitta), -(Math.sin(angle) * radius)) * 180 / Math.PI) - 90,
                                });
                            }

                            return seats;
                        }

                        for (let index = 0; index < count; index++) {
                            seats.push({
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
                                    x: point.x + (point.normalX * offset) + (outX * (seatGap - chairInset)),
                                    y: point.y + (point.normalY * offset) + (outY * (seatGap - chairInset)),
                                    rotation: rotationFor(outX, outY),
                                });
                            }
                        });

                        return seats;
                    }

                    const bySide = table.seats_by_side_json || { top: 0, right: 0, bottom: 0, left: 0 };
                    const addSide = (side, count) => {
                        count = Number(count || 0);
                        for (let index = 0; index < count; index++) {
                            const ratio = (index + 1) / (count + 1);
                            if (side === 'top') seats.push({ x: -width / 2 + width * ratio, y: -height / 2 - seatGap + chairInset, rotation: 0 });
                            if (side === 'right') seats.push({ x: width / 2 + seatGap - chairInset, y: -height / 2 + height * ratio, rotation: 90 });
                            if (side === 'bottom') seats.push({ x: -width / 2 + width * ratio, y: height / 2 + seatGap - chairInset, rotation: 180 });
                            if (side === 'left') seats.push({ x: -width / 2 - seatGap + chairInset, y: -height / 2 + height * ratio, rotation: 270 });
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
                    return this.seats(table)[index] || { x: 0, y: 0, rotation: 0 };
                },
                seatTransform(table, index) {
                    const seat = this.seatAt(table, index);

                    return `translate(${seat.x}, ${seat.y}) rotate(${seat.rotation})`;
                },
                zoomIn() {
                    this.changeZoom(0.1);
                },
                zoomOut() {
                    this.changeZoom(-0.1);
                },
                changeZoom(delta) {
                    this.zoom = Math.min(2.5, Math.max(0.35, Number((this.zoom + delta).toFixed(2))));
                    this.scheduleViewportSave();
                },
                zoomWheel(event) {
                    const direction = event.deltaY < 0 ? 1 : -1;
                    this.changeZoom(direction * 0.03);
                },
                openAddTablesModal(type = 'round') {
                    this.addForm = { type, count: 1, seats: 8 };
                    this.showAddTablesModal = true;
                },
                openDeleteTableModal() {
                    if (! this.selectedTable()) return;

                    this.showDeleteTableModal = true;
                },
                openDeleteElementModal() {
                    if (! this.selectedElement()) return;

                    this.showDeleteElementModal = true;
                },
                async addTable(type) {
                    const table = await this.$wire.addTable(type);
                    this.tables.push(table);
                    this.select(table.id);
                },
                async addTablesFromModal() {
                    await this.$wire.addTables(this.addForm.type, Number(this.addForm.count || 1), Number(this.addForm.seats || 0));
                    this.showAddTablesModal = false;
                    window.location.reload();
                },
                async deleteSelectedTable() {
                    const table = this.selectedTable();
                    if (! table) return;

                    await this.$wire.deleteTable(table.id);
                    this.showDeleteTableModal = false;
                    window.location.reload();
                },
                async addLayoutElement(type, shape = null) {
                    const newElementId = await this.$wire.addLayoutElement(type, shape);

                    if (newElementId) {
                        window.localStorage.setItem(`${this.selectionStorageKey()}-element`, String(newElementId));
                    }

                    window.location.reload();
                },
                async deleteSelectedElement() {
                    const element = this.selectedElement();
                    if (! element) return;

                    await this.$wire.deleteLayoutElement(element.id);
                    this.showDeleteElementModal = false;
                    window.location.reload();
                },
                async duplicateSelectedTable() {
                    const table = this.selectedTable();
                    if (! table) return;

                    const newTableId = await this.$wire.duplicateTable(table.id);

                    if (newTableId) {
                        window.localStorage.setItem(this.selectionStorageKey(), String(newTableId));
                    }

                    window.location.reload();
                },
                scheduleSave(delay = 700) {
                    clearTimeout(this.saveTimer);
                    this.saveStatus = 'Saving...';
                    this.saveTimer = setTimeout(() => this.save(), delay);
                },
                async save() {
                    this.tables.forEach((table) => {
                        if (this.isChairRow(table)) {
                            table.primary_dimension = this.chairRowWidth(table.seats_total);
                            table.secondary_dimension = 24;
                            table.curve_type = ['none', 'medium', 'high'].includes(table.curve_type) ? table.curve_type : 'none';
                        } else if (this.isLongTable(table)) {
                            table.primary_dimension = Math.max(100, Number(table.primary_dimension || 800));
                            table.secondary_dimension = 100;
                            table.curve_count = Math.max(0, Math.min(4, Number(table.curve_count || 0)));
                            table.curve_type = ['subtle', 'medium', 'strong'].includes(table.curve_type) ? table.curve_type : 'medium';
                        }
                    });
                    this.elements.forEach((element) => {
                        element.width = Math.max(20, Number(element.width || 120));
                        element.height = element.shape === 'circle'
                            ? element.width
                            : Math.max(20, Number(element.height || 80));
                        element.background_color = element.background_color && element.background_color !== 'transparent'
                            ? element.background_color
                            : '#f3eadc';
                    });
                    await this.$wire.saveTables(JSON.parse(JSON.stringify(this.tables)));
                    await this.$wire.saveLayoutElements(JSON.parse(JSON.stringify(this.elements)));
                    this.schedulePreviewSave(100);
                    this.saveStatus = 'Saved';
                },
                scheduleViewportSave(delay = 700) {
                    clearTimeout(this.viewportSaveTimer);
                    this.saveStatus = 'Saving...';
                    this.viewportSaveTimer = setTimeout(() => this.saveViewport(), delay);
                },
                async saveViewport() {
                    await this.$wire.saveViewportState({
                        zoom: this.zoom,
                        x: this.viewportX,
                        y: this.viewportY,
                    });
                    this.saveStatus = 'Saved';
                },
                schedulePreviewSave(delay = 900) {
                    clearTimeout(this.previewSaveTimer);
                    this.previewSaveTimer = setTimeout(() => this.savePreview(), delay);
                },
                async savePreview() {
                    const svg = this.$refs.svg.cloneNode(true);
                    svg.querySelectorAll('.wm-seat-rotate-handle').forEach((element) => element.remove());
                    svg.querySelectorAll('[style*="display: none"]').forEach((element) => element.remove());
                    svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                    svg.setAttribute('width', '1400');
                    svg.setAttribute('height', '900');
                    const style = document.createElementNS('http://www.w3.org/2000/svg', 'style');
                    style.textContent = `
                        .wm-seat-grid-line { stroke: rgba(121,112,102,.13); stroke-width: 1; }
                        .wm-seat-table-shape { fill: #fffdf9; stroke: #7a8f7b; stroke-width: 2.5; }
                        .wm-layout-element-shape { stroke: #a88f62; stroke-width: 2; stroke-dasharray: 6 4; }
                        .wm-layout-label { fill: #4b433b; font-size: 15px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; }
                        .wm-seat-label { fill: #2d2a26; font-size: 13px; font-weight: 900; text-anchor: middle; dominant-baseline: middle; }
                        .wm-seat-chair-seat { fill: #fffaf2; stroke: #9d8451; stroke-width: 1.4; }
                        .wm-seat-chair-back { fill: #d8c298; stroke: #9d8451; stroke-width: 1.4; }
                    `;
                    svg.insertBefore(style, svg.firstChild);

                    const source = new XMLSerializer().serializeToString(svg);
                    const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const image = new Image();

                    image.onload = async () => {
                        const canvas = document.createElement('canvas');
                        canvas.width = 1400;
                        canvas.height = 900;
                        const context = canvas.getContext('2d');
                        context.fillStyle = '#faf7f2';
                        context.fillRect(0, 0, canvas.width, canvas.height);
                        context.drawImage(image, 0, 0);
                        URL.revokeObjectURL(url);

                        await this.$wire.refreshPreviewImage();
                    };

                    image.onerror = async () => {
                        URL.revokeObjectURL(url);
                        await this.$wire.refreshPreviewImage();
                    };

                    image.src = url;
                },
            });
    </script>
    @endscript
</x-filament-panels::page>
