<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: "ProposalTitle";
            font-style: normal;
            font-weight: normal;
            src: url("{{ $fonts['title'] }}") format("truetype");
        }
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #35544f;
            font-family: DejaVu Serif, serif;
            font-size: 10.5px;
            line-height: 1.18;
        }
        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
            page-break-after: always;
            background: #ffffff;
        }
        .page:last-child { page-break-after: auto; }
        .box-blue { background: #d7fbff; }
        .title {
            font-family: "ProposalTitle", Helvetica, Arial, sans-serif;
            font-weight: normal;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-align: center;
            line-height: 1.18;
        }
        .script {
            font-style: italic;
            text-align: center;
        }
        .center { text-align: center; }
        .upper {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }
        .photo {
            position: absolute;
            object-fit: cover;
        }
        .body-copy {
            position: absolute;
            text-align: center;
            white-space: pre-line;
        }
        .body-copy p { margin: 0 0 3.3mm; }
        .small-copy { font-size: 9.5px; }
        .heading-box {
            box-sizing: content-box;
            position: absolute;
            height: 12.3mm;
            padding-top: 4.7mm;
            font-size: 16px;
        }
        .logo-mark {
            position: absolute;
            width: 14mm;
            height: 14mm;
            object-fit: contain;
        }
        .contact {
            position: absolute;
            width: 70mm;
            font-size: 10px;
            text-align: center;
        }
        .offer-box {
            box-sizing: content-box;
            position: absolute;
            overflow: hidden;
            padding: 6mm 7mm 5mm;
            text-align: center;
        }
        .offer-title {
            font-size: 13px;
            letter-spacing: 2px;
            line-height: 1.22;
            white-space: pre-line;
        }
        .offer-copy {
            margin-top: 8mm;
            font-size: 11px;
            letter-spacing: 1.5px;
            line-height: 1.22;
            white-space: pre-line;
        }
        .extras p {
            margin: 0 0 3.2mm;
            text-transform: uppercase;
        }
        .fine-print {
            position: absolute;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .rich p { margin: 0; }
        .rich ul, .rich ol { margin: 0; padding-left: 4mm; }
        .conditions-copy, .planning-copy { white-space:normal; }
        .conditions-copy p, .conditions-copy ul, .conditions-copy ol { margin:0 0 3.3mm; }
        .planning-copy p, .planning-copy ul, .planning-copy ol { margin:0 0 3.3mm; }
        @if ($preview)
        @font-face { font-family:"DejaVu Serif"; src:url("{{ $fonts['serif'] }}") format("truetype"); }
        @font-face { font-family:"DejaVu Sans"; src:url("{{ $fonts['sans'] }}") format("truetype"); }
        body { background:#eeeae5; padding:16px 0; display:flex; flex-direction:column; align-items:center; gap:24px; }
        .preview-sheet { position:relative; flex-shrink:0; box-shadow:0 4px 20px #0002; }
        [data-edit] { cursor:pointer; }
        [data-edit]:hover { outline:1px dashed #446a5a; outline-offset:2px; }
        .element-edit { position:absolute; z-index:50; width:28px; height:28px; border:1px solid #446a5a; border-radius:50%; background:#fff; color:#244d3a; cursor:pointer; font:18px sans-serif; box-shadow:0 1px 4px #0003; }
        .overflow-warning { position:absolute; bottom:2mm; left:2mm; z-index:60; background:#fff0d0; color:#8a3c00; font:10px sans-serif; padding:3px; }
        @endif
    </style>
</head>
<body>
    <section class="page">
        <img class="photo" data-edit="images.cover_bride" src="{{ $images['cover_bride'] }}" style="left: 17mm; top: 4.6mm; width: 82mm; height: 200.5mm;">

        <div class="box-blue" style="position: absolute; left: 108mm; top: 4.6mm; width: 82mm; height: 200.5mm;">
            <div data-edit="cover_title" class="title rich" style="position: absolute; left: 11mm; top: 26mm; width: 60mm; font-size: 26px;">
                {!! $rich['cover_title'] !!}
            </div>
            <div data-edit="cover_subtitle" class="script rich" style="position: absolute; left: 12mm; top: 66mm; width: 58mm;">{!! $rich['cover_subtitle'] !!}</div>
            <img data-edit="images.social_block" src="{{ $images['social_block'] }}" style="position: absolute; left: 20mm; bottom: 31mm; width: 42mm; height: auto;">
            <img class="logo-mark" data-edit="images.logo" src="{{ $images['logo'] }}" style="left: 31mm; bottom: 7mm; width: 21mm; height: 21mm;">
        </div>

        <img class="photo" data-edit="images.cover_venue" src="{{ $images['cover_venue'] }}" style="left: 200mm; top: 4.6mm; width: 82mm; height: 171mm;">
        <div data-edit="contact" class="contact rich" style="left: 206mm; top: 182mm;">
            {!! $rich['contact'] !!}
        </div>
    </section>

    <section class="page">
        <div data-edit="prices_heading" class="heading-box box-blue title rich" style="left: 12mm; top: 4.6mm; width: 75mm;">{!! $rich['prices_heading'] !!}</div>
        <div data-edit="service_heading" class="upper rich" style="position: absolute; left: 13mm; top: 31mm; width: 76mm; font-size: 12px;">{!! $rich['service_heading'] !!}</div>

        <div data-edit="planning_service" data-limit="154" class="body-copy rich planning-copy" style="left: 9mm; top: 48mm; width: 76mm;">{!! $rich['planning_service'] !!}</div>

        <img class="photo" data-edit="images.table_cypress" src="{{ $images['table_cypress'] }}" style="left: 108mm; top: 4.6mm; width: 82mm; height: 118.5mm;">
        <div data-edit="planning_service_right" data-limit="77" class="body-copy rich planning-copy" style="left: 105mm; top: 129mm; width: 87mm; font-size: 9px; line-height: 1.12;">{!! $rich['planning_service_right'] !!}</div>

        <div class="offer-box box-blue" style="left: 203mm; top: 7.5mm; width: 75mm; height: 64mm;">
            <div data-edit="proposal_title" class="title offer-title rich">{!! $rich['proposal_title'] !!}</div>
            @if ($venueName)<div class="title" style="font-size:10px">{{ $venueName }}</div>@endif
            <div data-edit="proposal_subtitle" class="title offer-title rich">{!! $rich['proposal_subtitle'] !!}</div>
            <div class="title offer-copy">{{ $data['offer_title'] }}<br>{{ $data['main_fee'] }}</div>
        </div>
        <img class="photo" data-edit="images.ceremony_hills" src="{{ $images['ceremony_hills'] }}" style="left: 207mm; top: 86mm; width: 82mm; height: 119mm;">
    </section>

    <section class="page">
        <img class="photo" data-edit="images.ceremony_view" src="{{ $images['ceremony_view'] }}" style="left: 10.5mm; top: 4.6mm; width: 75mm; height: 104mm;">
        <img class="photo" data-edit="images.dinner_garden" src="{{ $images['dinner_garden'] }}" style="left: 10.5mm; top: 121mm; width: 75mm; height: 84mm;">
        <img class="photo" data-edit="images.ceremony_altar" src="{{ $images['ceremony_altar'] }}" style="left: 108mm; top: 4.6mm; width: 82mm; height: 90mm;">
        <img class="photo" data-edit="images.table_white" src="{{ $images['table_white'] }}" style="left: 108mm; top: 113.5mm; width: 82mm; height: 91mm;">
        <img class="photo" data-edit="images.table_strip" src="{{ $images['table_strip'] }}" style="left: 202mm; top: 4.6mm; width: 90mm; height: 66.15mm;">

        <div data-edit="extras_heading" class="heading-box box-blue title rich" style="left: 210mm; top: 75mm; width: 75mm; height: 11.8mm; padding-top: 4.2mm; font-size: 15px;">{!! $rich['extras_heading'] !!}</div>
        <div class="body-copy extras" style="left: 206mm; top: 97.5mm; width: 82mm; font-size: 8.8px; line-height: 1.08;">
            @foreach ($data['extra_rows'] as $row)
                <p>{{ $row['label'] }}: {{ $row['amount'] ? '€ ' . number_format((float) $row['amount'], 0, ',', '.') : '' }}</p>
            @endforeach
        </div>
        <div data-edit="vat_note" class="fine-print rich" style="left: 223mm; top: 195mm;">{!! $rich['vat_note'] !!}</div>
    </section>

    <section class="page">
        <img class="photo" data-edit="images.wedding_ceremony" src="{{ $images['wedding_ceremony'] }}" style="left: 11mm; top: 6mm; width: 85mm; height: 75mm;">
        <img class="photo" data-edit="images.olive_ceremony" src="{{ $images['olive_ceremony'] }}" style="left: 11mm; top: 88.5mm; width: 85mm; height: 115mm;">
        <img class="photo" data-edit="images.table_film" src="{{ $images['table_film'] }}" style="left: 107mm; top: 6mm; width: 87mm; height: 94mm;">
        <img class="photo" data-edit="images.table_rustic" src="{{ $images['table_rustic'] }}" style="left: 107mm; top: 111mm; width: 87mm; height: 93mm;">

        <div data-edit="confirmation_heading" class="heading-box box-blue title rich" style="left: 204mm; top: 3.8mm; width: 90mm; height: 23mm; padding-top: 6mm; font-size: 15px; line-height: 1.45;">{!! $rich['confirmation_heading'] !!}</div>
        <div data-edit="conditions" data-limit="160" class="body-copy rich conditions-copy" style="left: 211mm; top: 44mm; width: 75mm;">{!! $rich['conditions'] !!}</div>
    </section>
@if ($preview)
<script>
(() => {
    const pages = [...document.querySelectorAll('.page')];
    const controls = [];
    for (const page of pages) {
        const shell = document.createElement('div');
        shell.className = 'preview-sheet';
        page.before(shell); shell.append(page);
        page.style.transformOrigin = 'top left';
        for (const target of page.querySelectorAll('[data-edit]')) {
            const edit = () => window.parent.postMessage({type:'proposal-edit', key:target.dataset.edit}, window.location.origin);
            target.addEventListener('click', edit);
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'element-edit'; button.textContent = '✎';
            button.setAttribute('aria-label', 'Edit ' + target.dataset.edit.replaceAll('_', ' '));
            button.title = button.getAttribute('aria-label');
            button.addEventListener('click', edit); page.append(button);
            controls.push({page, target, button});
        }
    }
    function layout() {
        const scale = Math.max(0.15, Math.min(1, (document.documentElement.clientWidth - 32) / pages[0].offsetWidth));
        for (const page of pages) {
            page.style.transform = `scale(${scale})`;
            page.parentElement.style.width = (page.offsetWidth * scale) + 'px';
            page.parentElement.style.height = (page.offsetHeight * scale) + 'px';
            page.querySelector('.overflow-warning')?.remove();
        }
        for (const {page, target, button} of controls) {
            const box = target.getBoundingClientRect(), origin = page.getBoundingClientRect();
            button.style.left = Math.max(0, (box.right-origin.left)/scale-27) + 'px';
            button.style.top = Math.max(0, (box.top-origin.top)/scale) + 'px';
            const exceeds = target.dataset.limit && target.offsetHeight > Number(target.dataset.limit)*96/25.4;
            if ((exceeds || box.bottom > origin.bottom+1 || target.scrollHeight > target.clientHeight+2) && !page.querySelector('.overflow-warning')) {
                const warning=document.createElement('div'); warning.className='overflow-warning';
                warning.textContent='Text may exceed its frame. Shorten the content and check the PDF.'; page.append(warning);
            }
        }
        window.parent.postMessage({type:'proposal-height', height:document.body.scrollHeight+32}, window.location.origin);
    }
    window.addEventListener('resize', layout);
    window.addEventListener('load', layout);
    document.fonts.ready.then(layout);
    layout();
})();
</script>
@endif
</body>
</html>
