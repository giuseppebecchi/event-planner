<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $payload = $record->form_payload ?? [];
        $sections = $this->getSections();
        $answeredCount = $this->getAnsweredCount();
    @endphp

    <style>
        .lead-form-data-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
            color: #5f5147;
        }

        .lead-form-data-hero,
        .lead-form-data-section,
        .lead-form-data-meta-card,
        .lead-form-data-question {
            box-sizing: border-box;
        }

        .lead-form-data-hero {
            border: 1px solid #eadfd4;
            border-radius: 26px;
            padding: 22px;
            background: linear-gradient(135deg, #fffdf9 0%, #f6eee6 100%);
            box-shadow: 0 16px 36px rgba(93, 70, 55, 0.08);
        }

        .lead-form-data-hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            flex-wrap: wrap;
        }

        .lead-form-data-eyebrow {
            margin: 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.28em;
            color: #b49479;
            font-weight: 700;
        }

        .lead-form-data-hero h2,
        .lead-form-data-section-title,
        .lead-form-data-question-title {
            margin: 0;
            color: #5d4637;
        }

        .lead-form-data-hero h2 {
            margin-top: 8px;
            font-size: 26px;
            line-height: 1.1;
        }

        .lead-form-data-hero-copy {
            margin-top: 14px;
            max-width: 860px;
            font-size: 14px;
            line-height: 1.8;
            color: #876f5d;
        }

        .lead-form-data-completion {
            min-width: 160px;
            text-align: right;
            border: 1px solid #e6d7ca;
            border-radius: 20px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 12px 24px rgba(93, 70, 55, 0.06);
        }

        .lead-form-data-completion-value {
            margin-top: 8px;
            font-size: 28px;
            line-height: 1;
            color: #5d4637;
            font-weight: 700;
        }

        .lead-form-data-completion-label {
            font-size: 12px;
            color: #8f6f57;
        }

        .lead-form-data-meta {
            margin-top: 16px;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .lead-form-data-meta-card {
            border: 1px solid #eadfd4;
            border-radius: 20px;
            padding: 18px;
            background: #ffffff;
            box-shadow: 0 12px 24px rgba(93, 70, 55, 0.05);
        }

        .lead-form-data-meta-value,
        .lead-form-data-meta-link {
            margin-top: 10px;
            font-size: 15px;
            line-height: 1.7;
            color: #5d4637;
            word-break: break-word;
        }

        .lead-form-data-meta-link {
            display: block;
            text-decoration: none;
            color: #6e533f;
        }

        .lead-form-data-meta-link:hover {
            color: #9b7454;
            text-decoration: underline;
        }

        .lead-form-data-sections {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .lead-form-data-section {
            border: 1px solid #eadfd4;
            border-radius: 24px;
            padding: 20px;
            background: #fffdf9;
            box-shadow: 0 16px 34px rgba(93, 70, 55, 0.05);
        }

        .lead-form-data-section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            padding-bottom: 18px;
            border-bottom: 1px solid #efe4da;
        }

        .lead-form-data-section-copy {
            margin-top: 8px;
            font-size: 14px;
            line-height: 1.7;
            color: #8f6f57;
        }

        .lead-form-data-section-pill,
        .lead-form-data-question-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .lead-form-data-section-pill {
            background: #f3ebe3;
            color: #8f6f57;
        }

        .lead-form-data-question-grid {
            margin-top: 16px;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .lead-form-data-question {
            border: 1px solid #e9ded4;
            border-radius: 22px;
            padding: 18px;
            background: #ffffff;
            box-shadow: 0 10px 22px rgba(93, 70, 55, 0.05);
        }

        .lead-form-data-question.is-empty {
            border-style: dashed;
            border-color: #dccbbe;
            background: #fffcf8;
        }

        .lead-form-data-question-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .lead-form-data-question-pill.is-answered {
            background: #f4ece4;
            color: #8c694f;
        }

        .lead-form-data-question-pill.is-empty {
            background: #f7f1ea;
            color: #c1a893;
        }

        .lead-form-data-required {
            display: inline-block;
            border-radius: 999px;
            padding: 6px 12px;
            background: #f5ede5;
            color: #8f6f57;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            white-space: nowrap;
        }

        .lead-form-data-question-title {
            margin-top: 10px;
            font-size: 16px;
            line-height: 1.7;
            font-weight: 700;
        }

        .lead-form-data-question-help {
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.6;
            color: #9b816b;
        }

        .lead-form-data-answer {
            margin-top: 14px;
            padding: 14px;
            border-radius: 16px;
            background: #fcfaf7;
        }

        .lead-form-data-answer-empty {
            margin-top: 16px;
        }

        .lead-form-data-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .lead-form-data-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 8px 12px;
            background: #f4ece4;
            color: #7a5d49;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .lead-form-data-answer-text {
            font-size: 14px;
            line-height: 1.9;
            white-space: pre-line;
            color: #5f5147;
        }

        .lead-form-data-answer-empty-text {
            font-size: 14px;
            color: #b3a295;
            font-weight: 600;
        }

        .lead-form-data-empty-state {
            border: 1px dashed #d9c7b7;
            border-radius: 24px;
            background: #ffffff;
            padding: 40px 24px;
            text-align: center;
            color: #8f6f57;
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            .lead-form-data-meta,
            .lead-form-data-question-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="lead-form-data-page">
        <div class="lead-form-data-hero">
            <div class="lead-form-data-hero-top">
                <div>
                    <p class="lead-form-data-eyebrow">Questionnaire dossier</p>
                    <h2>Form answers for {{ $record->couple_name }}</h2>
                    <div class="lead-form-data-hero-copy">
                        A clean internal reading of the client questionnaire, organized by topic so each answer is easier to assess during qualification and follow-up.
                    </div>
                </div>

                <div class="lead-form-data-completion">
                    <p class="lead-form-data-eyebrow">Completion</p>
                    <div class="lead-form-data-completion-value">{{ $answeredCount }}</div>
                    <div class="lead-form-data-completion-label">answered fields</div>
                </div>
            </div>

            <div class="lead-form-data-meta">
                <div class="lead-form-data-meta-card">
                    <p class="lead-form-data-eyebrow">Public link</p>
                    <a href="{{ $record->public_form_url }}" target="_blank" class="lead-form-data-meta-link">
                        {{ $record->public_form_url }}
                    </a>
                </div>

                <div class="lead-form-data-meta-card">
                    <p class="lead-form-data-eyebrow">Form sent at</p>
                    <div class="lead-form-data-meta-value">
                        {{ $record->form_sent_at?->format('d/m/Y H:i') ?? 'Not marked yet' }}
                    </div>
                </div>

                <div class="lead-form-data-meta-card">
                    <p class="lead-form-data-eyebrow">Form completed at</p>
                    <div class="lead-form-data-meta-value">
                        {{ $record->form_completed_at?->format('d/m/Y H:i') ?? 'Not completed yet' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="lead-form-data-sections">
            @forelse($sections as $section)
                @php
                    $questions = $this->getQuestionsForSection($section);
                @endphp

                <section class="lead-form-data-section">
                    <div class="lead-form-data-section-head">
                        <div>
                            <p class="lead-form-data-eyebrow">{{ $section['title'] }}</p>
                            <h3 class="lead-form-data-section-title">{{ $section['title'] }}</h3>
                            <div class="lead-form-data-section-copy">{{ $section['description'] }}</div>
                        </div>
                        <div class="lead-form-data-section-pill">
                            {{ $questions->count() }} questions
                        </div>
                    </div>

                    <div class="lead-form-data-question-grid">
                        @foreach($questions as $question)
                            @php
                                $answer = $payload[$question['key']] ?? null;
                                $hasAnswer = $this->hasAnswer($answer);
                            @endphp

                            <article class="lead-form-data-question {{ $hasAnswer ? '' : 'is-empty' }}">
                                <div class="lead-form-data-question-top">
                                    <div>
                                        <span class="lead-form-data-question-pill {{ $hasAnswer ? 'is-answered' : 'is-empty' }}">
                                            {{ $hasAnswer ? 'Answer recorded' : 'Waiting for answer' }}
                                        </span>
                                        <h4 class="lead-form-data-question-title">{{ $question['label'] }}</h4>
                                    </div>

                                    @if ($question['required'] ?? false)
                                        <span class="lead-form-data-required">Required</span>
                                    @endif
                                </div>

                                @if (filled($question['help'] ?? null))
                                    <div class="lead-form-data-question-help">{{ $question['help'] }}</div>
                                @endif

                                {!! $this->getFormattedAnswer($answer) !!}
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="lead-form-data-empty-state">
                    No form data has been submitted for this lead yet.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
