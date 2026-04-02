<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Support\LeadQuestionnaire;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewLeadFormData extends Page
{
    use InteractsWithRecord;

    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.resources.lead-resource.pages.view-lead-form-data';

    protected static ?string $breadcrumb = 'Questionnaire';

    protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return sprintf('%s Questionnaire', $this->getRecordTitle());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openPublicForm')
                ->label('Open public form')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getRecord()->public_form_url, shouldOpenInNewTab: true),
            Action::make('markAsSent')
                ->label('Mark as sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action(function (): void {
                    $this->getRecord()->forceFill([
                        'form_sent_at' => now(),
                    ])->save();
                }),
            Action::make('regenerateLink')
                ->label('Regenerate link')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->forceFill([
                        'public_form_hash' => Str::lower(Str::random(32)),
                    ])->save();
                }),
        ];
    }

    public function getQuestionMap(): array
    {
        return LeadQuestionnaire::byKey();
    }

    public function getSections(): array
    {
        return [
            [
                'title' => 'Couple profile',
                'description' => 'Identity, background and what defines them as a couple.',
                'keys' => [
                    'names',
                    'nationality',
                    'about_yourselves',
                    'important_as_couple',
                    'describe_yourselves',
                ],
            ],
            [
                'title' => 'Wedding vision',
                'description' => 'Date, destination, ceremony, atmosphere and venue direction.',
                'keys' => [
                    'wedding_period',
                    'estimated_guest_count',
                    'desired_region',
                    'ceremony_type',
                    'wedding_vision',
                    'pinterest_board',
                    'venue_types',
                    'booking_plan',
                    'wedding_end_time',
                    'table_setup',
                    'flower_palette',
                ],
            ],
            [
                'title' => 'Guests and experience',
                'description' => 'Accommodation, side events and operational expectations.',
                'keys' => [
                    'guest_accommodation_payment',
                    'additional_events',
                    'can_travel_before_wedding',
                    'planner_expectations',
                    'already_hired_suppliers',
                    'additional_notes',
                ],
            ],
            [
                'title' => 'Budget and priorities',
                'description' => 'Spending expectations and the services they value most.',
                'keys' => [
                    'wedding_budget',
                    'side_events_budget',
                    'priority_services',
                    'videographer_interest',
                ],
            ],
            [
                'title' => 'Marketing and source',
                'description' => 'Visibility permissions and where the lead came from.',
                'keys' => [
                    'social_media_consent',
                    'discovery_source',
                ],
            ],
        ];
    }

    public function getQuestionsForSection(array $section): Collection
    {
        $questionMap = $this->getQuestionMap();

        return collect($section['keys'])
            ->map(fn (string $key): ?array => isset($questionMap[$key]) ? ['key' => $key, ...$questionMap[$key]] : null)
            ->filter();
    }

    public function hasAnswer(mixed $value): bool
    {
        if (is_array($value)) {
            return count(array_filter(Arr::flatten($value), fn (mixed $item): bool => filled($item))) > 0;
        }

        return filled($value);
    }

    public function getAnsweredCount(): int
    {
        return collect($this->getRecord()->form_payload ?? [])
            ->filter(fn (mixed $value): bool => $this->hasAnswer($value))
            ->count();
    }

    public function getFormattedAnswer(mixed $value): HtmlString
    {
        if (blank($value)) {
            return new HtmlString('<div class="lead-form-data-answer-empty"><span class="lead-form-data-answer-empty-text">No answer yet</span></div>');
        }

        if (is_array($value)) {
            $items = collect(Arr::flatten($value))
                ->filter(fn (mixed $item): bool => filled($item))
                ->map(fn (mixed $item): string => sprintf(
                    '<span class="lead-form-data-tag">%s</span>',
                    e((string) $item),
                ))
                ->implode(' ');

            return new HtmlString('<div class="lead-form-data-answer"><div class="lead-form-data-tags">' . $items . '</div></div>');
        }

        return new HtmlString('<div class="lead-form-data-answer"><div class="lead-form-data-answer-text">' . e((string) $value) . '</div></div>');
    }
}
