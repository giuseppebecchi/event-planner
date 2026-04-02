<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fairytale Italy Weddings | Thank You</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at top, rgba(255, 255, 255, 0.85), transparent 28%),
                linear-gradient(180deg, #fbf5ef 0%, #f2e6da 100%);
            font-family: 'Manrope', sans-serif;
            color: #553f31;
            padding: 24px;
        }

        .card {
            width: min(720px, 100%);
            border-radius: 36px;
            padding: 42px 34px;
            background: rgba(255, 252, 248, 0.94);
            border: 1px solid rgba(233, 217, 203, 0.9);
            box-shadow: 0 24px 70px rgba(118, 86, 60, 0.13);
            text-align: center;
        }

        img {
            width: 96px;
            height: auto;
        }

        .eyebrow {
            margin: 18px 0 10px;
            font-size: 11px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #9a7a61;
        }

        h1 {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.8rem, 6vw, 4.5rem);
            line-height: 0.95;
        }

        p {
            margin: 18px auto 0;
            max-width: 560px;
            line-height: 1.8;
            color: #7f6758;
        }

        .pill {
            display: inline-block;
            margin-top: 22px;
            border-radius: 999px;
            padding: 12px 18px;
            background: #f2e6da;
            color: #7a5d49;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/logo.png') }}" alt="Fairytale Italy Weddings">
        <p class="eyebrow">Fairytale Italy Weddings</p>
        <h1>Thank you for sharing your vision.</h1>
        <p>
            Your questionnaire has already been received and carefully saved. We will review your answers and come back to you as soon as possible.
        </p>
        <div class="pill">
            {{ $alreadySubmitted ?? false ? 'Questionnaire already submitted' : 'Submission completed' }}
        </div>
    </div>
</body>
</html>
