<x-filament-panels::page>
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
            .lead-budget-columns {
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
                                        <input class="lead-budget-input" type="text" wire:model="data.budget_vendors.{{ $index }}.amount">
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
                                        <td><input class="lead-budget-input" type="text" wire:model="data.budget_wedding_planner.{{ $index }}.amount"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="lead-budget-panel">
                        <h3 class="lead-budget-panel-title">Wedding planner extra services</h3>
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
                                @foreach(($data['budget_wedding_planner_extra_services'] ?? []) as $index => $row)
                                    <tr>
                                        <td><textarea class="lead-budget-textarea" wire:model="data.budget_wedding_planner_extra_services.{{ $index }}.label"></textarea></td>
                                        <td><input class="lead-budget-input" type="text" wire:model="data.budget_wedding_planner_extra_services.{{ $index }}.amount"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="lead-budget-panel">
                        <h3 class="lead-budget-panel-title">Wedding planner special packages</h3>
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
                                @foreach(($data['budget_wedding_planner_special_packages'] ?? []) as $index => $row)
                                    <tr>
                                        <td>
                                            <textarea class="lead-budget-textarea" wire:model="data.budget_wedding_planner_special_packages.{{ $index }}.label"></textarea>
                                            <button class="lead-budget-remove" type="button" wire:click="removeSpecialPackage({{ $index }})">Remove row</button>
                                        </td>
                                        <td><input class="lead-budget-input" type="text" wire:model="data.budget_wedding_planner_special_packages.{{ $index }}.amount"></td>
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
