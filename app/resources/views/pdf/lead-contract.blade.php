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

        @page {
            margin: 24mm 22mm 22mm;
        }

        body {
            margin: 0;
            color: #1f2f2a;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.6px;
            line-height: 1.58;
        }

        .page-border {
            position: fixed;
            top: -14mm;
            right: -12mm;
            bottom: -12mm;
            left: -12mm;
            border: 2.2px solid #3f7538;
        }

        .doc-header {
            position: fixed;
            top: -15mm;
            left: 0;
            right: 0;
            height: 13mm;
            border-bottom: 0.5px solid #b9c8b5;
            color: #446158;
            font-family: "ProposalTitle", Helvetica, Arial, sans-serif;
            font-size: 8.2px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .doc-header .brand {
            position: absolute;
            left: 0;
            top: 4mm;
        }

        .doc-header .meta {
            position: absolute;
            right: 0;
            top: 4mm;
            text-align: right;
        }

        .doc-footer {
            position: fixed;
            right: 0;
            bottom: -14mm;
            left: 0;
            height: 12mm;
            border-top: 0.5px solid #b9c8b5;
            color: #2f403a;
            font-size: 7.4px;
        }

        .doc-footer .left {
            position: absolute;
            left: 0;
            top: 2.3mm;
        }

        .doc-footer .right {
            position: absolute;
            right: 0;
            top: 2.3mm;
        }

        .cover {
            margin: 5mm 0 7mm;
            padding-bottom: 5mm;
            border-bottom: 1px solid #d7e1d4;
            text-align: center;
        }

        .cover img {
            width: 25mm;
            height: 25mm;
            object-fit: contain;
            margin-bottom: 3mm;
        }

        .cover-title {
            margin: 0;
            color: #36554d;
            font-family: "ProposalTitle", Helvetica, Arial, sans-serif;
            font-size: 18px;
            font-weight: normal;
            letter-spacing: 2.2px;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .summary {
            width: 100%;
            margin: 5mm 0 2mm;
            border-collapse: collapse;
            color: #2f403a;
            font-size: 8.4px;
        }

        .summary td {
            width: 25%;
            padding: 2.4mm 3mm;
            border: 0.5px solid #d7e1d4;
            vertical-align: top;
        }

        .summary .label {
            display: block;
            margin-bottom: 0.8mm;
            color: #738178;
            font-family: "ProposalTitle", Helvetica, Arial, sans-serif;
            font-size: 6.8px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .contract-content {
            text-align: justify;
        }

        .contract-content h1 {
            margin: 0 0 6mm;
            color: #36554d;
            font-family: "ProposalTitle", Helvetica, Arial, sans-serif;
            font-size: 15px;
            font-weight: normal;
            letter-spacing: 1.7px;
            line-height: 1.35;
            text-align: center;
            text-transform: uppercase;
        }

        .contract-content h2 {
            margin: 7mm 0 2.4mm;
            padding: 1.8mm 2.4mm;
            border-left: 3px solid #3f7538;
            background: #eef6ec;
            color: #2f4b44;
            font-family: "ProposalTitle", Helvetica, Arial, sans-serif;
            font-size: 10.8px;
            font-weight: normal;
            letter-spacing: 1px;
            line-height: 1.3;
            page-break-after: avoid;
        }

        .contract-content p {
            margin: 0 0 2.8mm;
        }

        .contract-content ul,
        .contract-content ol {
            margin: 0 0 3.2mm 5.8mm;
            padding-left: 4.5mm;
        }

        .contract-content li {
            margin-bottom: 1.8mm;
            padding-left: 1mm;
        }

        .contract-content strong {
            color: #162923;
            font-weight: bold;
        }

        .contract-content .missing-value {
            color: #7b6a2f;
            font-style: italic;
        }

        .contract-content a {
            color: #2e5d8a;
            text-decoration: none;
        }

        .contract-content table {
            width: 100%;
            margin: 4mm 0;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .contract-content td,
        .contract-content th {
            padding: 2mm;
            border: 0.5px solid #ccd9c8;
            vertical-align: top;
        }

        .contract-content blockquote {
            margin: 4mm 0;
            padding: 2.5mm 4mm;
            border-left: 3px solid #c8ab55;
            background: #fbfaf4;
        }

        .contract-content h2:nth-of-type(14),
        .contract-content h2:nth-of-type(15),
        .contract-content h2:nth-of-type(16) {
            page-break-before: auto;
        }
    </style>
</head>
<body>
    <div class="page-border"></div>

    <header class="doc-header">
        <div class="brand">Fairytale Italy Weddings</div>
        <div class="meta">Wedding Planner Agreement</div>
    </header>

    <footer class="doc-footer">
        <div class="left">Wedding Planner Agreement</div>
        <div class="right"></div>
    </footer>

    <section class="cover">
        <img src="{{ $images['logo'] }}" alt="">
        <h1 class="cover-title">Wedding Planner Contract</h1>

        <table class="summary">
            <tr>
                <td><span class="label">Client</span>{{ $summary['couple'] }}</td>
                <td><span class="label">Wedding Date</span>{{ $summary['date'] }}</td>
                <td><span class="label">Location</span>{{ $summary['location'] }}</td>
                <td><span class="label">Issued</span>{{ $summary['issued_at'] }}</td>
            </tr>
        </table>
    </section>

    <main class="contract-content">
        {!! $contentHtml !!}
    </main>
</body>
</html>
