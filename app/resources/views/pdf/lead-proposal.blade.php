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
            position: absolute;
            height: 17mm;
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
            margin: 0 0 4.9mm;
            text-transform: uppercase;
        }
        .fine-print {
            position: absolute;
            font-size: 8.5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <section class="page">
        <img class="photo" src="{{ $images['cover_bride'] }}" style="left: 17mm; top: 4.6mm; width: 82mm; height: 200.5mm;">

        <div class="box-blue" style="position: absolute; left: 108mm; top: 4.6mm; width: 82mm; height: 200.5mm;">
            <div class="title" style="position: absolute; left: 11mm; top: 26mm; width: 60mm; font-size: 26px;">
                Fairytale<br>Italy<br>Weddings
            </div>
            <div class="script" style="position: absolute; left: 12mm; top: 66mm; width: 58mm;">Where dreams come true</div>
            <img src="{{ $images['social_block'] }}" style="position: absolute; left: 20mm; bottom: 31mm; width: 42mm; height: auto;">
            <img class="logo-mark" src="{{ $images['logo'] }}" style="left: 31mm; bottom: 7mm; width: 21mm; height: 21mm;">
        </div>

        <img class="photo" src="{{ $images['cover_venue'] }}" style="left: 200mm; top: 4.6mm; width: 82mm; height: 171mm;">
        <div class="contact" style="left: 206mm; top: 182mm;">
            Florence, Italy<br>
            +39 353 4171643<br>
            hello@fairytaleitalyweddings.com<br>
            www.fairytaleitalyweddings.com
        </div>
    </section>

    <section class="page">
        <div class="heading-box box-blue title" style="left: 12mm; top: 4.6mm; width: 75mm;">Our prices</div>
        <div class="upper" style="position: absolute; left: 13mm; top: 31mm; width: 76mm; font-size: 12px;">Wedding planning service</div>

        <div class="body-copy" style="left: 9mm; top: 48mm; width: 76mm;">
            @foreach ($data['planning_rows_left'] as $row)
                <p>{{ $row }}</p>
            @endforeach
        </div>

        <img class="photo" src="{{ $images['table_cypress'] }}" style="left: 108mm; top: 4.6mm; width: 82mm; height: 118.5mm;">
        <div class="body-copy" style="left: 105mm; top: 129mm; width: 87mm; font-size: 9px; line-height: 1.12;">
            @foreach ($data['planning_rows_right'] as $row)
                <p>{{ $row }}</p>
            @endforeach
        </div>

        <div class="offer-box box-blue" style="left: 203mm; top: 7.5mm; width: 89mm; height: 75mm;">
            <div class="title offer-title">{{ $data['proposal_title'] }}</div>
            <div class="title offer-copy">{{ $data['offer_title'] }}<br>{{ $data['main_fee'] }}</div>
        </div>
        <img class="photo" src="{{ $images['ceremony_hills'] }}" style="left: 207mm; top: 86mm; width: 82mm; height: 119mm;">
    </section>

    <section class="page">
        <img class="photo" src="{{ $images['ceremony_view'] }}" style="left: 10.5mm; top: 4.6mm; width: 75mm; height: 104mm;">
        <img class="photo" src="{{ $images['dinner_garden'] }}" style="left: 10.5mm; top: 121mm; width: 75mm; height: 84mm;">
        <img class="photo" src="{{ $images['ceremony_altar'] }}" style="left: 108mm; top: 4.6mm; width: 82mm; height: 90mm;">
        <img class="photo" src="{{ $images['table_white'] }}" style="left: 108mm; top: 113.5mm; width: 82mm; height: 91mm;">
        <img class="photo" src="{{ $images['table_strip'] }}" style="left: 202mm; top: 4.6mm; width: 90mm; height: 31.5mm;">

        <div class="heading-box box-blue title" style="left: 210mm; top: 42mm; width: 75mm;">Extra services:</div>
        <div class="body-copy extras small-copy" style="left: 206mm; top: 70mm; width: 82mm;">
            @foreach ($data['extra_rows'] as $row)
                <p>{{ $row['label'] }}: {{ $row['amount'] ? '€ ' . number_format((float) $row['amount'], 0, ',', '.') : '' }}</p>
            @endforeach
        </div>
        <div class="fine-print" style="left: 223mm; top: 195mm;">All the prices include VAT</div>
    </section>

    <section class="page">
        <img class="photo" src="{{ $images['wedding_ceremony'] }}" style="left: 11mm; top: 6mm; width: 85mm; height: 75mm;">
        <img class="photo" src="{{ $images['olive_ceremony'] }}" style="left: 11mm; top: 88.5mm; width: 85mm; height: 115mm;">
        <img class="photo" src="{{ $images['table_film'] }}" style="left: 107mm; top: 6mm; width: 87mm; height: 94mm;">
        <img class="photo" src="{{ $images['table_rustic'] }}" style="left: 107mm; top: 111mm; width: 87mm; height: 93mm;">

        <div class="heading-box box-blue title" style="left: 204mm; top: 3.8mm; width: 90mm; height: 29mm; padding-top: 6mm; font-size: 15px; line-height: 1.45;">To proceed with the<br>confirmation</div>
        <div class="body-copy" style="left: 212mm; top: 44mm; width: 73mm;">
            @foreach ($data['confirmation_rows'] as $row)
                <p>{{ $row }}</p>
            @endforeach
        </div>
        <div class="body-copy" style="left: 211mm; top: 175mm; width: 75mm;">
            <p>This offer is valid 30 days from today (until {{ $data['valid_until'] }}). After that limit, a new quote might apply.</p>
            <p>No reservation has been made at this stage.</p>
        </div>
    </section>
</body>
</html>
