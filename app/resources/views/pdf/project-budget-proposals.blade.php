<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #2d2a26;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            background: #fff;
        }
        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
            page-break-after: always;
            background: #fbf8f4;
        }
        .page:last-child { page-break-after: auto; }
        .cover-bg,
        .hero-bg {
            position: absolute;
            inset: 0;
            width: 297mm;
            height: 210mm;
        }
        .cover-bg,
        .hero-bg,
        .gallery-img {
            object-fit: cover;
        }
        .cover-shade {
            position: absolute;
            inset: 0;
            background: rgba(20, 31, 40, 0.48);
        }
        .cover-panel {
            position: absolute;
            left: 18mm;
            bottom: 18mm;
            width: 128mm;
            padding: 12mm;
            background: rgba(255, 255, 255, 0.88);
        }
        .logo {
            width: 42mm;
            height: auto;
            margin-bottom: 12mm;
        }
        .kicker {
            margin: 0 0 4mm;
            color: #9a7a39;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2.8px;
            text-transform: uppercase;
        }
        .cover-title {
            margin: 0;
            font-family: DejaVu Serif, serif;
            font-size: 29px;
            font-weight: normal;
            line-height: 1.05;
            text-transform: uppercase;
        }
        .cover-meta {
            margin-top: 9mm;
            color: #5f5953;
            font-size: 12px;
        }
        .cover-meta div { margin-bottom: 2.5mm; }
        .cover-count {
            position: absolute;
            right: 18mm;
            top: 18mm;
            padding: 5mm 7mm;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.5);
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .proposal-shade {
            position: absolute;
            inset: 0;
            background: rgba(18, 28, 36, 0.38);
        }
        .proposal-title {
            margin: 0 0 2mm;
            font-family: DejaVu Serif, serif;
            font-size: 27px;
            font-weight: normal;
            line-height: 1.05;
            color: #2d2a26;
        }
        .proposal-subtitle {
            margin: 0 0 7mm;
            color: #5f5953;
            font-size: 13px;
        }
        .content-card {
            position: absolute;
            left: 15mm;
            top: 14mm;
            width: 122mm;
            min-height: 181mm;
            padding: 7mm 8mm;
            background: rgba(255, 255, 255, 0.94);
        }
        .section-title {
            margin: 0 0 2mm;
            color: #2e4a62;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .summary {
            margin: 0 0 4mm;
            color: #4d4740;
            font-size: 10.2px;
            line-height: 1.35;
            white-space: pre-line;
        }
        .facts {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
        }
        .facts td {
            padding: 0.9mm 0;
            border-bottom: 1px solid #e8e0d7;
            vertical-align: top;
        }
        .facts td:first-child {
            width: 40mm;
            color: #8b847d;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .facts tr.quote-item td:first-child {
            color: #4d4740;
            font-size: 10px;
            font-weight: normal;
            letter-spacing: 0;
            text-transform: none;
        }
        .facts tr.quote-item td:last-child {
            width: 34mm;
        }
        .facts tr.total td {
            font-weight: 700;
        }
        .facts tr.total td:first-child {
            color: #2d2a26;
        }
        .facts tr.total td:last-child,
        .facts tr.quote-item td:last-child {
            text-align: right;
            white-space: nowrap;
        }
        .compact-facts {
            width: 100%;
            border-collapse: collapse;
        }
        .compact-facts td {
            width: 33.333%;
            padding: 0 3mm 0.3mm 0;
            border-bottom: 0;
        }
        .compact-label {
            display: block;
            margin-bottom: 0.1mm;
            color: #5f5953;
            font-size: 7.8px;
            font-weight: normal;
            letter-spacing: 0;
            text-transform: none;
            white-space: nowrap;
        }
        .compact-value {
            display: block;
            color: #2d2a26;
            font-size: 9.4px;
        }
        .gallery {
            position: absolute;
            right: 20mm;
            top: 18mm;
            width: 118mm;
            height: 168mm;
        }
        .gallery-thumb {
            width: 118mm;
            height: 50mm;
            margin: 0 0 7mm;
            padding: 1.2mm;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            border: 0.45mm solid rgba(46, 74, 98, 0.42);
        }
        .gallery-thumb img {
            width: 115.6mm;
            height: 47.6mm;
        }
        .empty-image {
            width: 100%;
            height: 100%;
            padding-top: 40mm;
            color: #7d756e;
            text-align: center;
            background: #efe7de;
        }
        .footer {
            position: absolute;
            left: 15mm;
            bottom: 7mm;
            color: rgba(255, 255, 255, 0.78);
            font-size: 8.5px;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <section class="page">
        @if ($coverBackground)
            <img class="cover-bg" src="{{ $coverBackground }}" alt="">
        @endif
        <div class="cover-shade"></div>
        <div class="cover-count">{{ $proposals->count() }} {{ $proposals->count() === 1 ? 'proposal' : 'proposals' }}</div>
        <div class="cover-panel">
            @if ($logo)
                <img class="logo" src="{{ $logo }}" alt="">
            @endif
            <p class="kicker">{{ $usesFallback ? 'Received proposals' : 'Shortlist presentation' }}</p>
            <h1 class="cover-title">{{ $budget->category?->label_it ?? 'Supplier' }} proposals</h1>
            <div class="cover-meta">
                <div><strong>{{ $partners ?: $project->name }}</strong></div>
                <div>{{ collect([$project->locality, $project->region])->filter()->implode(', ') ?: 'Italy' }}</div>
                <div>{{ $dateRange ?: $project->event_start_date?->format('F j, Y') }}</div>
                <div>{{ $project->final_guest_count ?: $project->estimated_guest_count ?: '-' }} guests</div>
            </div>
        </div>
    </section>

    @foreach ($proposals as $proposal)
        @php
            $supplier = $proposal->supplier;
            $images = $supplier?->images ?? collect();
            $heroImage = $images->firstWhere('image_type', 'hero') ?? $images->first();
            $hero = $imageResolver($heroImage?->image_path);
            $gallery = $images
                ->reject(fn ($image): bool => $heroImage && (int) $image->id === (int) $heroImage->id)
                ->take(3)
                ->map(fn ($image): ?string => $galleryImageResolver($image->image_path))
                ->filter()
                ->values();
            $address = collect([
                $supplier?->address_line_1,
                $supplier?->address_line_2,
                $supplier?->postal_code,
                $supplier?->city,
                $supplier?->province,
                $supplier?->region,
                $supplier?->country,
            ])->filter()->implode(', ');
            $website = $supplier?->loc_website;
            $hasRoomInfo = $supplier && (
                (bool) $supplier->loc_has_rooms
                || filled($supplier->loc_room_count)
                || filled($supplier->loc_stay_guest_max)
                || filled($supplier->loc_min_nights)
            );
            $roomDetails = collect([
                ['label' => 'Room count', 'value' => filled($supplier?->loc_room_count) ? (string) $supplier->loc_room_count : null],
                ['label' => 'Max overnight guests', 'value' => filled($supplier?->loc_stay_guest_max) ? (string) $supplier->loc_stay_guest_max : null],
                ['label' => 'Minimum nights', 'value' => filled($supplier?->loc_min_nights) ? (string) $supplier->loc_min_nights : null],
            ])->filter(fn (array $item): bool => filled($item['value']))->values();
            $availableDates = collect($proposal->location_available_dates ?? [])
                ->map(fn ($date): string => trim((string) $date))
                ->filter()
                ->values();
            $costItems = collect($proposal->cost_items_json ?? [])
                ->filter(fn ($item): bool => is_array($item) && (filled($item['label'] ?? null) || ($item['amount'] ?? null) !== null))
                ->values();
        @endphp

        <section class="page">
            @if ($hero)
                <img class="hero-bg" src="{{ $hero }}" alt="">
            @endif
            <div class="proposal-shade"></div>

            <div class="content-card">
                <p class="kicker">Proposal {{ $loop->iteration }}</p>
                <h2 class="proposal-title">{{ $supplier?->name ?? 'Supplier' }}</h2>
                <div class="proposal-subtitle">
                    {{ collect([$supplier?->loc_locality, $supplier?->city, $supplier?->region])->filter()->implode(' · ') }}
                </div>

                <p class="section-title">Proposal summary</p>
                <p class="summary">{{ $proposal->proposal_summary ?: $proposal->response_text ?: 'No proposal summary has been added yet.' }}</p>

                <p class="section-title">Main information</p>
                <table class="facts">
                    @if ($address)
                        <tr>
                            <td>Address</td>
                            <td>{{ $address }}</td>
                        </tr>
                    @endif
                    @if ($website)
                        <tr>
                            <td>Website</td>
                            <td>{{ $website }}</td>
                        </tr>
                    @endif
                    @if ($supplier?->email)
                        <tr>
                            <td>Email</td>
                            <td>{{ $supplier->email }}</td>
                        </tr>
                    @endif
                    @if ($supplier?->phone)
                        <tr>
                            <td>Phone</td>
                            <td>{{ $supplier->phone }}</td>
                        </tr>
                    @endif
                    @if ($supplier?->loc_structure_type)
                        <tr>
                            <td>Structure</td>
                            <td>{{ \App\Models\Supplier::LOCATION_STRUCTURE_TYPES[$supplier->loc_structure_type] ?? $supplier->loc_structure_type }}</td>
                        </tr>
                    @endif
                    @if ($supplier?->loc_guest_max)
                        <tr>
                            <td>Capacity</td>
                            <td>Up to {{ $supplier->loc_guest_max }} guests</td>
                        </tr>
                    @endif
                    @if ($hasRoomInfo)
                        <tr>
                            <td>Rooms</td>
                            <td>
                                @if (! $supplier->loc_has_rooms)
                                    Not available
                                @elseif ($roomDetails->isNotEmpty())
                                    <table class="compact-facts">
                                        <tr>
                                            @foreach ($roomDetails as $item)
                                                <td>
                                                    <span class="compact-label">{{ $item['label'] }}</span>
                                                    <span class="compact-value">{{ $item['value'] }}</span>
                                                </td>
                                            @endforeach
                                            @for ($i = $roomDetails->count(); $i < 3; $i++)
                                                <td></td>
                                            @endfor
                                        </tr>
                                    </table>
                                @else
                                    Available
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if ($supplier?->loc_overview)
                        <tr>
                            <td>Overview</td>
                            <td>{{ $supplier->loc_overview }}</td>
                        </tr>
                    @endif
                </table>

                <p class="section-title">Quote</p>
                <table class="facts">
                    @if ($availableDates->isNotEmpty())
                        <tr>
                            <td>Venue availability dates</td>
                            <td>{{ $availableDates->implode(', ') }}</td>
                        </tr>
                    @endif
                    @foreach ($costItems as $item)
                        <tr class="quote-item">
                            <td>{{ filled($item['label'] ?? null) ? $item['label'] : 'Cost item' }}</td>
                            <td>{{ $money($item['amount'] ?? null) }}</td>
                        </tr>
                    @endforeach
                    @if ($proposal->costs_and_conditions)
                        <tr>
                            <td>Costs and conditions</td>
                            <td>{{ $proposal->costs_and_conditions }}</td>
                        </tr>
                    @endif
                    <tr class="total">
                        <td>Total</td>
                        <td>{{ $money($proposal->proposed_amount) }}</td>
                    </tr>
                </table>
            </div>

            <div class="gallery">
                @if ($gallery->isNotEmpty())
                    @foreach ($gallery as $image)
                        <div class="gallery-thumb">
                            <img class="gallery-img" src="{{ $image }}" alt="">
                        </div>
                    @endforeach
                @else
                    <div class="gallery-thumb">
                        <div class="empty-image">No gallery images in supplier archive</div>
                    </div>
                @endif
            </div>

            <div class="footer">{{ $project->name }} · {{ $budget->category?->label_it ?? 'Proposals' }}</div>
        </section>
    @endforeach
</body>
</html>
