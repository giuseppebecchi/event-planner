<x-filament-panels::page>
    @php
        $budgetTotals = $this->getBudgetTotals();
        $budgetDifference = $budgetTotals['difference'];
    @endphp

    <style>
        .lead-budget-layout {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .lead-budget-eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            color: #b49479;
        }

        .lead-budget-copy {
            margin-top: 8px;
            font-size: 14px;
            line-height: 1.75;
            color: #8f6f57;
            max-width: 860px;
        }

        .lead-budget-columns {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .lead-budget-panel {
            border: 1px solid #eadfd4;
            border-radius: 18px;
            background: #fffdf9;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(93, 70, 55, 0.04);
        }

        .lead-budget-panel + .lead-budget-panel {
            margin-top: 14px;
        }

        .lead-budget-recap {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) repeat(3, minmax(0, 0.65fr));
            gap: 12px;
            align-items: stretch;
            border: 1px solid #eadfd4;
            border-radius: 18px;
            background: #fffdf9;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(93, 70, 55, 0.04);
        }

        .lead-budget-recap-intro {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }

        .lead-budget-recap-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #5d4637;
        }

        .lead-budget-recap-copy {
            margin: 0;
            font-size: 13px;
            line-height: 1.55;
            color: #8f6f57;
        }

        .lead-budget-recap-card {
            border: 1px solid #eadfd4;
            border-radius: 14px;
            background: #fff8f1;
            padding: 12px;
        }

        .lead-budget-recap-label {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #9e846f;
        }

        .lead-budget-recap-value {
            margin: 6px 0 0;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.15;
            color: #5d4637;
        }

        .lead-budget-recap-value.is-positive {
            color: #2f7d45;
        }

        .lead-budget-recap-value.is-negative {
            color: #bd5a41;
        }

        .lead-budget-breakdown {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            padding-top: 2px;
        }

        .lead-budget-breakdown-item {
            border-top: 1px solid #f1e8df;
            padding-top: 10px;
        }

        .lead-budget-breakdown-label {
            margin: 0;
            font-size: 11px;
            color: #9e846f;
        }

        .lead-budget-breakdown-value {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: 700;
            color: #5d4637;
        }

        .lead-budget-panel-title {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #b49479;
        }

        .lead-budget-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .lead-budget-table col.label-col {
            width: 52%;
        }

        .lead-budget-table col.notes-col {
            width: 28%;
        }

        .lead-budget-table col.amount-col {
            width: 20%;
        }

        .lead-budget-table col.add-to-budget-col {
            width: 18%;
        }

        .lead-budget-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #9e846f;
            padding: 8px 10px;
            border-bottom: 1px solid #eadfd4;
        }

        .lead-budget-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1e8df;
            vertical-align: top;
        }

        .lead-budget-table tr:last-child td {
            border-bottom: 0;
        }

        .lead-budget-input,
        .lead-budget-textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #e1d3c6;
            border-radius: 8px;
            background: #ffffff;
            padding: 8px 10px;
            font-size: 13px;
            line-height: 1.45;
            color: #5d4637;
        }

        .lead-budget-textarea {
            min-height: 56px;
            resize: vertical;
        }

        .lead-budget-input:focus,
        .lead-budget-textarea:focus {
            outline: none;
            border-color: #c4a17d;
            box-shadow: 0 0 0 3px rgba(196, 161, 125, 0.12);
        }

        .lead-budget-checkbox-cell {
            vertical-align: middle;
        }

        .lead-budget-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #5d4637;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
            cursor: pointer;
        }

        .lead-budget-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #8a6a53;
            flex: 0 0 auto;
        }

        .lead-budget-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 12px;
        }

        .lead-budget-add-row {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #d8c5b3;
            border-radius: 10px;
            background: #fff8f1;
            color: #8a6a53;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .lead-budget-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            color: #b07c5d;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            margin-top: 6px;
        }

        .lead-budget-submit {
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 1100px) {
            .lead-budget-columns,
            .lead-budget-recap,
            .lead-budget-breakdown {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="lead-budget-layout">
        <div>
            <div>
                <p class="lead-budget-eyebrow">Lead budget</p>
                <div class="lead-budget-copy">
                    On the left you can manage the estimated supplier budget by category. On the right you can define the planning fee, extra services, and custom special packages for the wedding planner.
                </div>
            </div>
        </div>

        <section class="lead-budget-recap">
            <div class="lead-budget-recap-intro">
                <p class="lead-budget-eyebrow">Total recap</p>
                <h3 class="lead-budget-recap-title">Budget total summary</h3>
                <p class="lead-budget-recap-copy">
                    Includes vendor budget, wedding planner fee, and extra services or special packages selected with Add to budget.
                </p>
            </div>
            <div class="lead-budget-recap-card">
                <p class="lead-budget-recap-label">Total budget</p>
                <p class="lead-budget-recap-value">EUR {{ number_format($budgetTotals['grand_total'], 2, ',', '.') }}</p>
            </div>
            <div class="lead-budget-recap-card">
                <p class="lead-budget-recap-label">Client budget</p>
                <p class="lead-budget-recap-value">
                    {{ $budgetTotals['client_budget'] !== null ? 'EUR ' . number_format($budgetTotals['client_budget'], 2, ',', '.') : '-' }}
                </p>
            </div>
            <div class="lead-budget-recap-card">
                <p class="lead-budget-recap-label">Difference</p>
                <p class="lead-budget-recap-value {{ $budgetDifference !== null ? ($budgetDifference > 0 ? 'is-negative' : ($budgetDifference < 0 ? 'is-positive' : '')) : '' }}">
                    @if ($budgetDifference === null)
                        -
                    @else
                        {{ $budgetDifference === 0.0 ? 'EUR 0,00' : (($budgetDifference > 0 ? '+ ' : '- ') . 'EUR ' . number_format(abs($budgetDifference), 2, ',', '.')) }}
                    @endif
                </p>
            </div>
            <div class="lead-budget-breakdown">
                <div class="lead-budget-breakdown-item">
                    <p class="lead-budget-breakdown-label">Vendor budget</p>
                    <p class="lead-budget-breakdown-value">EUR {{ number_format($budgetTotals['vendors_total'], 2, ',', '.') }}</p>
                </div>
                <div class="lead-budget-breakdown-item">
                    <p class="lead-budget-breakdown-label">Wedding planner</p>
                    <p class="lead-budget-breakdown-value">EUR {{ number_format($budgetTotals['wedding_planner_total'], 2, ',', '.') }}</p>
                </div>
                <div class="lead-budget-breakdown-item">
                    <p class="lead-budget-breakdown-label">Extra services</p>
                    <p class="lead-budget-breakdown-value">EUR {{ number_format($budgetTotals['extra_services_total'], 2, ',', '.') }}</p>
                </div>
                <div class="lead-budget-breakdown-item">
                    <p class="lead-budget-breakdown-label">Special packages</p>
                    <p class="lead-budget-breakdown-value">EUR {{ number_format($budgetTotals['special_packages_total'], 2, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <form wire:submit="save">
            <div class="lead-budget-columns">
                <div class="lead-budget-panel">
                    <h3 class="lead-budget-panel-title">Vendor budget</h3>

                    <table class="lead-budget-table">
                        <colgroup>
                            <col class="label-col">
                            <col class="notes-col">
                            <col class="amount-col">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Notes</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($data['budget_vendors'] ?? []) as $index => $row)
                                <tr>
                                    <td>
                                        <input type="hidden" wire:model="data.budget_vendors.{{ $index }}.category_id">
                                        <input class="lead-budget-input" type="text" wire:model="data.budget_vendors.{{ $index }}.label">
                                    </td>
                                    <td>
                                        <input class="lead-budget-input" type="text" placeholder="notes" wire:model="data.budget_vendors.{{ $index }}.notes">
                                    </td>
                                    <td>
                                        <input class="lead-budget-input" type="text" wire:model.live.debounce.500ms="data.budget_vendors.{{ $index }}.amount">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <div class="lead-budget-panel">
                        <h3 class="lead-budget-panel-title">Wedding planner</h3>
                        <table class="lead-budget-table">
                            <colgroup>
                                <col style="width: 76%">
                                <col class="amount-col">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($data['budget_wedding_planner'] ?? []) as $index => $row)
                                    <tr>
                                        <td><textarea class="lead-budget-textarea" wire:model="data.budget_wedding_planner.{{ $index }}.label"></textarea></td>
                                        <td><input class="lead-budget-input" type="text" wire:model.live.debounce.500ms="data.budget_wedding_planner.{{ $index }}.amount"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="lead-budget-panel">
                        <h3 class="lead-budget-panel-title">Wedding planner extra services</h3>
                        <table class="lead-budget-table">
                            <colgroup>
                                <col style="width: 58%">
                                <col class="add-to-budget-col">
                                <col class="amount-col">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Add to budget</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($data['budget_wedding_planner_extra_services'] ?? []) as $index => $row)
                                    <tr>
                                        <td><textarea class="lead-budget-textarea" wire:model="data.budget_wedding_planner_extra_services.{{ $index }}.label"></textarea></td>
                                        <td class="lead-budget-checkbox-cell">
                                            <label class="lead-budget-checkbox-label">
                                                <input class="lead-budget-checkbox" type="checkbox" wire:model.live="data.budget_wedding_planner_extra_services.{{ $index }}.add_to_budget">
                                                <span>Selected</span>
                                            </label>
                                        </td>
                                        <td><input class="lead-budget-input" type="text" wire:model.live.debounce.500ms="data.budget_wedding_planner_extra_services.{{ $index }}.amount"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="lead-budget-panel">
                        <h3 class="lead-budget-panel-title">Wedding planner special packages</h3>
                        <table class="lead-budget-table">
                            <colgroup>
                                <col style="width: 58%">
                                <col class="add-to-budget-col">
                                <col class="amount-col">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Add to budget</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($data['budget_wedding_planner_special_packages'] ?? []) as $index => $row)
                                    <tr>
                                        <td>
                                            <textarea class="lead-budget-textarea" wire:model="data.budget_wedding_planner_special_packages.{{ $index }}.label"></textarea>
                                            <button class="lead-budget-remove" type="button" wire:click="removeSpecialPackage({{ $index }})">Remove row</button>
                                        </td>
                                        <td class="lead-budget-checkbox-cell">
                                            <label class="lead-budget-checkbox-label">
                                                <input class="lead-budget-checkbox" type="checkbox" wire:model.live="data.budget_wedding_planner_special_packages.{{ $index }}.add_to_budget">
                                                <span>Selected</span>
                                            </label>
                                        </td>
                                        <td><input class="lead-budget-input" type="text" wire:model.live.debounce.500ms="data.budget_wedding_planner_special_packages.{{ $index }}.amount"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="lead-budget-actions">
                            <button class="lead-budget-add-row" type="button" wire:click="addSpecialPackage">Add row</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lead-budget-submit">
                <x-filament::button type="submit" size="lg">
                    Save budget
                </x-filament::button>
            </div>
        </form>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
