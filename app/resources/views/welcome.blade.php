<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Wedding Manager') }}</title>
    @include('partials.favicons')
    <style>
        :root {
            --ink: #2e2a24;
            --muted: #756f66;
            --paper: #fbf7ef;
            --cream: #f2eadc;
            --sage: #7a8f7b;
            --gold: #c9a96a;
            --white: #fffdf8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: Georgia, 'Times New Roman', serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(201, 169, 106, .22), transparent 28rem),
                radial-gradient(circle at 82% 72%, rgba(122, 143, 123, .2), transparent 30rem),
                linear-gradient(135deg, var(--paper), var(--cream));
        }

        .page {
            position: relative;
            display: grid;
            min-height: 100vh;
            place-items: center;
            padding: 32px 20px;
            overflow: hidden;
        }

        .page::before,
        .page::after {
            position: absolute;
            width: 420px;
            height: 420px;
            content: '';
            border: 1px solid rgba(46, 42, 36, .08);
            border-radius: 999px;
        }

        .page::before {
            top: -180px;
            right: -120px;
        }

        .page::after {
            bottom: -220px;
            left: -140px;
        }

        .card {
            position: relative;
            z-index: 1;
            width: min(100%, 760px);
            padding: clamp(36px, 7vw, 72px);
            text-align: center;
            background: rgba(255, 253, 248, .82);
            border: 1px solid rgba(46, 42, 36, .08);
            border-radius: 36px;
            box-shadow: 0 28px 90px rgba(46, 42, 36, .13);
            backdrop-filter: blur(14px);
        }

        .logo {
            display: block;
            width: min(100%, 292px);
            height: auto;
            margin: 0 auto 34px;
        }

        .eyebrow {
            margin: 0 0 18px;
            color: var(--sage);
            font-family: Optima, Candara, 'Segoe UI', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 620px;
            margin: 0 auto;
            font-size: clamp(34px, 7vw, 66px);
            font-weight: 400;
            line-height: .98;
            letter-spacing: -.045em;
        }

        .intro {
            max-width: 560px;
            margin: 24px auto 0;
            color: var(--muted);
            font-family: Optima, Candara, 'Segoe UI', sans-serif;
            font-size: clamp(17px, 2.4vw, 20px);
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
            margin-top: 38px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 24px;
            color: var(--white);
            font-family: Optima, Candara, 'Segoe UI', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .08em;
            text-decoration: none;
            text-transform: uppercase;
            background: var(--ink);
            border-radius: 999px;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .button:hover {
            background: #1d1a16;
            box-shadow: 0 12px 30px rgba(46, 42, 36, .18);
            transform: translateY(-2px);
        }

        .button.secondary {
            color: var(--ink);
            background: transparent;
            border: 1px solid rgba(46, 42, 36, .16);
        }

        .button.secondary:hover {
            background: rgba(255, 253, 248, .72);
        }

        .mark {
            width: 68px;
            height: 1px;
            margin: 32px auto 0;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        @media (max-width: 560px) {
            .card {
                border-radius: 26px;
            }

            .actions {
                align-items: stretch;
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card" aria-labelledby="home-title">
            <img class="logo" src="{{ asset('images/logo-positive.png') }}" alt="{{ config('app.name', 'Wedding Manager') }}">
            <p class="eyebrow">Event planning software</p>
            <h1 id="home-title">Gestione completa per matrimoni ed eventi.</h1>
            <p class="intro">
                Una piattaforma operativa per seguire lead, clienti, fornitori, budget, documenti, RSVP,
                timeline e disposizione tavoli in un unico flusso di lavoro.
            </p>
            <div class="actions" aria-label="Azioni principali">
                <a class="button" href="{{ url('/admin') }}">Accedi all'area admin</a>
                <a class="button secondary" href="{{ url('/up') }}">Stato applicazione</a>
            </div>
            <div class="mark" aria-hidden="true"></div>
        </section>
    </main>
</body>
</html>
