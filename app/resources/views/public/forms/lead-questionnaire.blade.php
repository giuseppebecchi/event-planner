<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fairytale Italy Weddings | Couple Questionnaire</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7efe7;
            --paper: rgba(255, 252, 248, 0.92);
            --card: #fffdfa;
            --ink: #4e382b;
            --muted: #8f7664;
            --line: #eadbce;
            --accent: #b58b62;
            --accent-strong: #8f6846;
            --accent-soft: #f1e3d6;
            --success: #4f7a57;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.75), transparent 32%),
                radial-gradient(circle at top right, rgba(220, 195, 174, 0.45), transparent 28%),
                linear-gradient(180deg, #fbf5ef 0%, var(--bg) 100%);
            min-height: 100vh;
        }

        .page-shell {
            width: min(1120px, calc(100% - 32px));
            margin: 32px auto 48px;
        }

        .hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 36px;
            padding: 36px;
            background:
                linear-gradient(140deg, rgba(95, 69, 51, 0.95), rgba(156, 123, 96, 0.9)),
                url('{{ asset('images/icons/WEDDING-PLANNER-2.jpg') }}') center/cover no-repeat;
            box-shadow: 0 25px 80px rgba(101, 71, 49, 0.18);
            color: #fff8f1;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(55, 33, 18, 0.72), rgba(55, 33, 18, 0.2));
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 24px;
        }

        .hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 16px;
        }

        .brand img {
            width: 92px;
            height: auto;
        }

        .eyebrow {
            margin: 0 0 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            font-size: 11px;
            opacity: 0.82;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .hero h1 {
            font-size: clamp(2.7rem, 6vw, 4.8rem);
            line-height: 0.96;
            max-width: 680px;
        }

        .hero-copy {
            max-width: 760px;
            color: rgba(255, 248, 241, 0.9);
            font-size: 15px;
            line-height: 1.8;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-badge {
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .form-shell {
            margin-top: -38px;
            position: relative;
            z-index: 2;
            border-radius: 36px;
            background: var(--paper);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 24px 70px rgba(130, 99, 72, 0.12);
            padding: 28px;
        }

        .intro-card,
        .question-card {
            border-radius: 28px;
            background: var(--card);
            border: 1px solid var(--line);
            box-shadow: 0 10px 24px rgba(143, 104, 70, 0.05);
        }

        .intro-card {
            padding: 26px;
        }

        .intro-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 18px;
        }

        .intro-stat {
            border-radius: 22px;
            background: linear-gradient(180deg, #fff9f3 0%, #f8eee4 100%);
            border: 1px solid #f0dfd0;
            padding: 18px;
        }

        .intro-stat strong {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .intro-stat span {
            font-family: 'Cormorant Garamond', serif;
            font-size: 34px;
            color: var(--accent-strong);
        }

        form {
            display: grid;
            gap: 18px;
            margin-top: 22px;
        }

        .question-card {
            padding: 22px;
        }

        .question-head {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .question-index {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            flex: none;
            display: grid;
            place-items: center;
            background: linear-gradient(180deg, #f6e8da 0%, #e7d1bd 100%);
            color: var(--accent-strong);
            font-weight: 700;
            font-size: 13px;
        }

        .question-text h3 {
            font-size: clamp(1.45rem, 3vw, 2rem);
            color: var(--ink);
        }

        .question-text p {
            margin: 6px 0 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 14px;
        }

        .required-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            color: var(--accent-strong);
            background: var(--accent-soft);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .field {
            margin-top: 18px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            border: 1px solid #dfcdbc;
            border-radius: 18px;
            background: #fffdfb;
            padding: 16px 18px;
            font: inherit;
            color: var(--ink);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(181, 139, 98, 0.12);
            transform: translateY(-1px);
        }

        .choice-grid {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .choice {
            position: relative;
        }

        .choice input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .choice label {
            display: block;
            border-radius: 20px;
            border: 1px solid #e8d9cb;
            background: #fffdf9;
            padding: 16px 18px 16px 54px;
            color: var(--ink);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            line-height: 1.55;
        }

        .choice label::before {
            content: "";
            position: absolute;
            left: 18px;
            top: 18px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1.5px solid #c7a98e;
            background: #fff;
            transition: all 0.2s ease;
        }

        .choice.is-checkbox label::before {
            border-radius: 6px;
        }

        .choice input:checked + label {
            border-color: #c8a583;
            background: linear-gradient(180deg, #fff9f3 0%, #f8eee4 100%);
            box-shadow: 0 10px 20px rgba(181, 139, 98, 0.08);
        }

        .choice input:checked + label::before {
            border-color: var(--accent-strong);
            background: var(--accent-strong);
            box-shadow: inset 0 0 0 4px #fffaf5;
        }

        .errors,
        .success-banner {
            margin-bottom: 18px;
            border-radius: 22px;
            padding: 18px 20px;
        }

        .errors {
            background: #fff2f2;
            border: 1px solid #f2c5c5;
            color: #9f4747;
        }

        .errors ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }

        .submit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            padding-top: 10px;
        }

        .submit-note {
            max-width: 540px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .submit-button {
            appearance: none;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #9b7352 0%, #6d4d33 100%);
            color: #fffaf4;
            padding: 16px 28px;
            font: inherit;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 16px 26px rgba(103, 71, 49, 0.24);
        }

        .footer-note {
            margin-top: 22px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        @media (max-width: 860px) {
            .page-shell {
                width: min(100% - 20px, 1120px);
                margin-top: 20px;
            }

            .hero,
            .form-shell {
                border-radius: 28px;
                padding: 22px;
            }

            .intro-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                max-width: none;
            }

            .submit-row {
                align-items: stretch;
            }

            .submit-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <section class="hero">
            <div class="hero-inner">
                <div class="hero-top">
                    <div class="brand">
                        <img src="{{ asset('images/logo-neg.png') }}" alt="Fairytale Italy Weddings">
                        <div>
                            <p class="eyebrow">Fairytale Italy Weddings</p>
                            <p style="margin: 0; opacity: 0.85;">A bespoke questionnaire for your celebration in Italy.</p>
                        </div>
                    </div>
                    <div class="hero-badges">
                        <div class="hero-badge">Tailored Planning</div>
                        <div class="hero-badge">Italian Wedding Vision</div>
                        <div class="hero-badge">Private Couple Form</div>
                    </div>
                </div>

                <div>
                    <p class="eyebrow">Let’s see if we are a good match</p>
                    <h1>Your story, your vision, your wedding atmosphere.</h1>
                </div>

                <div class="hero-copy">
                    Dear Couple, wedding planning is a long and sometimes stressful process, and good chemistry with your planner matters.
                    This short questionnaire helps us understand your vision, your priorities, and the experience you are dreaming of in Italy.
                    Your answers will help us evaluate whether we are the right fit to create something truly memorable together.
                </div>
            </div>
        </section>

        <section class="form-shell">
            <div class="intro-card">
                <p class="eyebrow" style="color: var(--accent-strong);">Couple questionnaire</p>
                <h2 style="font-size: clamp(2rem, 4vw, 3rem); color: var(--ink);">A thoughtful start to your planning journey</h2>
                <p style="margin: 14px 0 0; color: var(--muted); line-height: 1.8;">
                    Take your time and answer honestly. The more precise your answers are, the better we can understand your wedding style,
                    expectations and practical needs.
                </p>

                <div class="intro-grid">
                    <div class="intro-stat">
                        <strong>Estimated time</strong>
                        <span>10 min</span>
                    </div>
                    <div class="intro-stat">
                        <strong>Questions</strong>
                        <span>{{ count($questions) }}</span>
                    </div>
                    <div class="intro-stat">
                        <strong>Lead</strong>
                        <span>{{ $lead->couple_name ?: 'Private' }}</span>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="errors">
                    <strong>Please review the highlighted information.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('public.lead-form.submit', $lead->public_form_hash) }}">
                @csrf

                @foreach ($questions as $index => $question)
                    <section class="question-card">
                        <div class="question-head">
                            <div class="question-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="question-text">
                                <h3>{{ $question['label'] }}</h3>

                                @if (filled($question['help'] ?? null))
                                    <p>{{ $question['help'] }}</p>
                                @endif

                                @if ($question['required'] ?? false)
                                    <div class="required-pill">Required</div>
                                @endif
                            </div>
                        </div>

                        <div class="field">
                            @if (($question['type'] ?? null) === 'textarea')
                                <textarea
                                    name="{{ $question['key'] }}"
                                    id="{{ $question['key'] }}"
                                    @required($question['required'] ?? false)
                                >{{ old($question['key']) }}</textarea>
                            @elseif (($question['type'] ?? null) === 'radio')
                                <div class="choice-grid">
                                    @foreach (($question['options'] ?? []) as $option)
                                        <div class="choice">
                                            <input
                                                type="radio"
                                                id="{{ $question['key'] }}-{{ md5($option) }}"
                                                name="{{ $question['key'] }}"
                                                value="{{ $option }}"
                                                @checked(old($question['key']) === $option)
                                            >
                                            <label for="{{ $question['key'] }}-{{ md5($option) }}">{{ $option }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif (($question['type'] ?? null) === 'checkboxes')
                                <div class="choice-grid">
                                    @foreach (($question['options'] ?? []) as $option)
                                        <div class="choice is-checkbox">
                                            <input
                                                type="checkbox"
                                                id="{{ $question['key'] }}-{{ md5($option) }}"
                                                name="{{ $question['key'] }}[]"
                                                value="{{ $option }}"
                                                @checked(in_array($option, old($question['key'], []), true))
                                            >
                                            <label for="{{ $question['key'] }}-{{ md5($option) }}">{{ $option }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <input
                                    type="text"
                                    name="{{ $question['key'] }}"
                                    id="{{ $question['key'] }}"
                                    value="{{ old($question['key']) }}"
                                    @required($question['required'] ?? false)
                                >
                            @endif
                        </div>
                    </section>
                @endforeach

                <div class="submit-row">
                    <p class="submit-note">
                        Thank you for taking the time to share your wedding vision with us. Once submitted, your answers will be reviewed by our team to understand whether we are the right match for your celebration.
                    </p>

                    <button class="submit-button" type="submit">Submit Questionnaire</button>
                </div>
            </form>

            <div class="footer-note">Fairytale Italy Weddings • crafted with care for your next chapter</div>
        </section>
    </div>
</body>
</html>
