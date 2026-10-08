@php
    $rows = $rows ?? [];
    $wirePrefix = $wirePrefix ?? null;
    $editable = $editable ?? false;
    $showEmpty = $showEmpty ?? true;
@endphp

<div class="wm-event-days" aria-label="Event days">
    @forelse ($rows as $eventDayIndex => $eventDay)
        @php($selected = (bool) ($eventDay['selected'] ?? false))
        @if ($editable || $selected)
            <div class="wm-event-day-row" wire:key="event-day-{{ $wirePrefix }}-{{ $eventDay['date'] }}">
                <label class="wm-event-day-choice">
                    <input
                        type="checkbox"
                        @if ($editable)
                            wire:model.live="{{ $wirePrefix }}.{{ $eventDayIndex }}.selected"
                        @else
                            checked disabled
                        @endif
                    >
                    <span>
                        <strong>{{ $eventDay['label'] }}</strong>
                        <small>{{ $eventDay['date'] }}</small>
                    </span>
                </label>

                @if ($editable)
                    <div class="wm-event-day-amount">
                        <span>EUR</span>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Optional"
                            wire:model="{{ $wirePrefix }}.{{ $eventDayIndex }}.amount"
                            @disabled(! $selected)
                        >
                    </div>
                @elseif (filled($eventDay['amount'] ?? null))
                    <span class="wm-event-day-readonly-amount">
                        EUR {{ number_format((float) $eventDay['amount'], 2, ',', '.') }}
                    </span>
                @endif
            </div>
        @endif
    @empty
        @if ($showEmpty)
            <p class="wm-event-days-empty">Set the project dates to assign this supplier to event days.</p>
        @endif
    @endforelse

    @if (! $editable && collect($rows)->where('selected', true)->isEmpty() && $showEmpty)
        <p class="wm-event-days-empty">No event days assigned.</p>
    @endif
</div>
