<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Support\LeadQuestionnaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicLeadFormController extends Controller
{
    public function show(string $hashId): View
    {
        $lead = $this->resolveLead($hashId);

        if ($lead->form_completed_at) {
            return view('public.forms.lead-questionnaire-success', [
                'lead' => $lead,
                'alreadySubmitted' => true,
            ]);
        }

        return view('public.forms.lead-questionnaire', [
            'lead' => $lead,
            'questions' => LeadQuestionnaire::definition(),
        ]);
    }

    public function submit(Request $request, string $hashId): RedirectResponse
    {
        $lead = $this->resolveLead($hashId);

        abort_if(filled($lead->form_completed_at), 409);

        $validated = $request->validate($this->rules(), [], $this->attributes());

        $payload = [];

        foreach (LeadQuestionnaire::definition() as $question) {
            $value = $validated[$question['key']] ?? null;

            if (is_array($value)) {
                $value = array_values($value);
            }

            $payload[$question['key']] = $value;
        }

        $lead->forceFill([
            'couple_name' => $payload['names'] ?: $lead->couple_name,
            'nationality' => $payload['nationality'] ?: $lead->nationality,
            'wedding_period' => $payload['wedding_period'] ?: $lead->wedding_period,
            'estimated_guest_count' => $this->extractInteger($payload['estimated_guest_count']) ?? $lead->estimated_guest_count,
            'desired_region' => $payload['desired_region'] ?: $lead->desired_region,
            'ceremony_type' => $this->normalizeCeremonyType($payload['ceremony_type']) ?? $lead->ceremony_type,
            'additional_events' => $payload['additional_events'] ?: $lead->additional_events,
            'style_description' => $payload['wedding_vision'] ?: $lead->style_description,
            'form_completed_at' => now(),
            'form_payload' => $payload,
        ])->save();

        return redirect()
            ->route('public.lead-form.show', $lead->public_form_hash)
            ->with('submitted', true);
    }

    protected function resolveLead(string $hashId): Lead
    {
        return Lead::query()
            ->where('public_form_hash', $hashId)
            ->firstOrFail();
    }

    protected function rules(): array
    {
        $rules = [];

        foreach (LeadQuestionnaire::definition() as $question) {
            $fieldRules = [];

            if ($question['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if (($question['type'] ?? null) === 'checkboxes') {
                $fieldRules[] = 'array';

                if (filled($question['max'] ?? null)) {
                    $fieldRules[] = 'max:' . $question['max'];
                }

                $rules[$question['key']] = $fieldRules;
                $rules[$question['key'] . '.*'] = [
                    'string',
                    Rule::in($question['options'] ?? []),
                ];

                continue;
            }

            $fieldRules[] = 'string';
            $fieldRules[] = 'max:5000';

            if (in_array($question['type'] ?? null, ['radio', 'select'], true)) {
                $fieldRules[] = Rule::in($question['options'] ?? []);
            }

            $rules[$question['key']] = $fieldRules;
        }

        return $rules;
    }

    protected function attributes(): array
    {
        return collect(LeadQuestionnaire::definition())
            ->mapWithKeys(fn (array $question): array => [$question['key'] => $question['label']])
            ->all();
    }

    protected function extractInteger(mixed $value): ?int
    {
        if (! is_string($value)) {
            return null;
        }

        preg_match('/\d+/', $value, $matches);

        if (! isset($matches[0])) {
            return null;
        }

        return (int) $matches[0];
    }

    protected function normalizeCeremonyType(mixed $value): ?string
    {
        return match ($value) {
            'Symbolic' => 'symbolic',
            'Civil' => 'civil',
            'Religious' => 'religious',
            default => null,
        };
    }
}
