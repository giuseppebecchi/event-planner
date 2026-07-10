@php
    $responseFormKey = $responseFormKey ?? 'proposal-' . $proposal->id;
@endphp

<div class="wm-scout-inline-form">
    <div class="wm-scout-form-section">
        <p class="wm-scout-form-section-title">Response status</p>
        <div class="wm-scout-inline-grid">
            <div>
                <label class="wm-scout-label" for="responded-at-{{ $responseFormKey }}">Response received at</label>
                <input
                    id="responded-at-{{ $responseFormKey }}"
                    type="datetime-local"
                    class="wm-scout-field"
                    wire:model="responseForm.responded_at"
                >
            </div>

            <div>
                <label class="wm-scout-label" for="availability-status-{{ $responseFormKey }}">Availability</label>
                <select
                    id="availability-status-{{ $responseFormKey }}"
                    class="wm-scout-select"
                    wire:model="responseForm.availability_status"
                >
                    @foreach (\App\Models\CategoryBudgetSupplier::AVAILABILITY_STATUS_OPTIONS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="wm-scout-form-section">
        <p class="wm-scout-form-section-title">Quote details</p>
        <div class="wm-scout-inline-grid">
            <div>
                <label class="wm-scout-label" for="proposed-amount-{{ $responseFormKey }}">Proposed amount</label>
                <input
                    id="proposed-amount-{{ $responseFormKey }}"
                    type="number"
                    step="0.01"
                    class="wm-scout-field"
                    wire:model="responseForm.proposed_amount"
                >
            </div>

            <div>
                <label class="wm-scout-label" for="response-scouting-status-{{ $responseFormKey }}">Scouting status</label>
                <select
                    id="response-scouting-status-{{ $responseFormKey }}"
                    class="wm-scout-select"
                    wire:model="responseForm.scouting_status"
                >
                    @foreach (\App\Models\CategoryBudgetSupplier::SCOUTING_STATUS_OPTIONS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-top: 0.75rem;">
            <div class="wm-scout-card-head" style="margin-bottom: 0.6rem;">
                <label class="wm-scout-label" style="margin: 0;">Cost breakdown</label>
                <x-filament::button type="button" size="sm" color="gray" icon="heroicon-m-plus" wire:click="addResponseCostItem">
                    Add item
                </x-filament::button>
            </div>

            <div class="wm-cost-items">
                @forelse (($responseForm['cost_items_json'] ?? []) as $costItemIndex => $costItem)
                    @php
                        $selectedCostItemLabel = (string) ($costItem['label'] ?? '');
                    @endphp
                    <div class="wm-cost-item-row" wire:key="cost-item-{{ $responseFormKey }}-{{ $costItemIndex }}">
                        <select
                            class="wm-scout-select wm-cost-item-select"
                            wire:model="responseForm.cost_items_json.{{ $costItemIndex }}.label"
                        >
                            <option value="">Item</option>
                            @if ($selectedCostItemLabel !== '' && ! collect($costItemSuggestions)->contains($selectedCostItemLabel))
                                <option value="{{ $selectedCostItemLabel }}">{{ $selectedCostItemLabel }}</option>
                            @endif
                            @foreach ($costItemSuggestions as $suggestion)
                                <option value="{{ $suggestion }}">{{ $suggestion }}</option>
                            @endforeach
                        </select>
                        <input
                            type="number"
                            step="0.01"
                            class="wm-scout-field"
                            placeholder="Amount"
                            wire:model="responseForm.cost_items_json.{{ $costItemIndex }}.amount"
                        >
                        <x-filament::button type="button" color="danger" size="sm" class="wm-cost-remove" wire:click="removeResponseCostItem({{ $costItemIndex }})">
                            Remove
                        </x-filament::button>
                    </div>
                @empty
                    <div class="wm-scout-empty">No cost items added yet.</div>
                @endforelse
            </div>

            @if ($this->hasPrefilledResponseCostItems())
                <p class="wm-cost-item-note">
                    Where possible, leave the items unchanged to allow quotes to be compared.
                </p>
            @endif
        </div>

        <input type="hidden" wire:model="responseForm.proposal_status">

        <div style="margin-top: 0.75rem;">
            <label class="wm-scout-label" for="proposal-summary-{{ $responseFormKey }}">Proposal summary</label>
            <textarea
                id="proposal-summary-{{ $responseFormKey }}"
                class="wm-scout-textarea"
                wire:model="responseForm.proposal_summary"
            ></textarea>
        </div>

        <div style="margin-top: 0.75rem;">
            <label class="wm-scout-label" for="response-text-{{ $responseFormKey }}">Response text</label>
            <textarea
                id="response-text-{{ $responseFormKey }}"
                class="wm-scout-textarea"
                wire:model="responseForm.response_text"
            ></textarea>
        </div>

        <div style="margin-top: 0.75rem;">
            <label class="wm-scout-label" for="costs-and-conditions-{{ $responseFormKey }}">Costs and conditions</label>
            <textarea
                id="costs-and-conditions-{{ $responseFormKey }}"
                class="wm-scout-textarea"
                wire:model="responseForm.costs_and_conditions"
            ></textarea>
        </div>
    </div>

    @if ($isLocationCategory)
        <div class="wm-scout-form-section">
            <p class="wm-scout-form-section-title">Venue dates</p>
            <div class="wm-scout-inline-grid">
                <div>
                    <label class="wm-scout-label" for="proposed-dates-{{ $responseFormKey }}">Proposed dates</label>
                    <textarea
                        id="proposed-dates-{{ $responseFormKey }}"
                        class="wm-scout-textarea"
                        wire:model="responseForm.proposed_dates"
                    ></textarea>
                </div>

                <div>
                    <label class="wm-scout-label" for="location-availability-dates-{{ $responseFormKey }}">Venue availability dates</label>
                    <textarea
                        id="location-availability-dates-{{ $responseFormKey }}"
                        class="wm-scout-textarea"
                        wire:model="responseForm.location_available_dates"
                    ></textarea>
                </div>
            </div>
        </div>
    @endif

    <div class="wm-scout-form-section">
        <p class="wm-scout-form-section-title">Internal notes and files</p>
        <div>
            <label class="wm-scout-label" for="response-notes-{{ $responseFormKey }}">Notes</label>
            <textarea
                id="response-notes-{{ $responseFormKey }}"
                class="wm-scout-textarea"
                wire:model="responseForm.notes"
            ></textarea>
        </div>

        @if (count($responseExistingAttachments))
            <div style="margin-top: 0.75rem;">
                <span class="wm-scout-label">Existing attachments</span>
                <div class="wm-scout-attachments">
                    @foreach ($responseExistingAttachments as $attachment)
                        <a
                            href="{{ $attachment['url'] }}"
                            target="_blank"
                            class="wm-scout-attachment"
                        >
                            {{ $attachment['title'] }}
                        </a>

                        <button
                            type="button"
                            class="wm-scout-attachment is-remove"
                            wire:click="removeExistingResponseAttachment({{ $attachment['id'] }})"
                        >
                            Remove
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="margin-top: 0.75rem;">
            <label class="wm-scout-label" for="response-uploads-{{ $responseFormKey }}">New attachments</label>
            <input
                id="response-uploads-{{ $responseFormKey }}"
                type="file"
                multiple
                class="wm-scout-field"
                wire:model="responseUploads"
            >
        </div>
    </div>

    <div class="wm-scout-actions" style="margin-top: 0;">
        <x-filament::button icon="heroicon-m-check" wire:click="saveRecordResponse">
            {{ $saveLabel ?? 'Save response' }}
        </x-filament::button>

        <x-filament::button color="gray" icon="heroicon-m-x-mark" wire:click="cancelRecordResponse">
            Cancel
        </x-filament::button>
    </div>
</div>
