<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use App\Models\Template;
use App\Notifications\LeadQuestionnaireRequestNotification;
use App\Support\LeadQuestionnaire;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

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
            Action::make('sendFormByMail')
                ->label('Send Form by mail')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send questionnaire by email?')
                ->modalDescription(fn (): string => 'The questionnaire link will be sent to '.$this->formatEmailRecipients(
                    $this->emailRecipients($this->getRecord())
                ).'.')
                ->action(function (): void {
                    $this->sendFormByMail();
                }),
            Action::make('openPublicForm')
                ->label('Open public form')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getRecord()->public_form_url, shouldOpenInNewTab: true),
            Action::make('markAsSent')
                ->label(fn (): string => $this->getRecord()->form_sent_at ? 'Marked as sent' : 'Mark as sent')
                ->icon(fn (): string => $this->getRecord()->form_sent_at ? 'heroicon-o-check-circle' : 'heroicon-o-paper-airplane')
                ->color(fn (): string => $this->getRecord()->form_sent_at ? 'success' : 'gray')
                ->disabled(fn (): bool => filled($this->getRecord()->form_sent_at))
                ->action(function (): void {
                    $lead = $this->getRecord();

                    $lead->forceFill([
                        'form_sent_at' => now(),
                    ])->save();

                    $this->record = $lead->refresh();

                    Notification::make()
                        ->title('Questionnaire marked as sent')
                        ->success()
                        ->send();
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

    public function sendFormByMail(): void
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();
        $recipients = $this->emailRecipients($lead);

        if ($recipients === []) {
            Notification::make()
                ->title('Client email missing')
                ->body('Add an email address to this lead before sending the questionnaire.')
                ->danger()
                ->send();

            return;
        }

        if (! Template::query()->where('slug', 'mail-lead-questionnaire')->where('language', 'en')->exists()) {
            Notification::make()
                ->title('Questionnaire email template missing')
                ->body('Missing template slug: mail-lead-questionnaire.')
                ->danger()
                ->send();

            return;
        }

        try {
            NotificationFacade::route('mail', $recipients)
                ->notify(new LeadQuestionnaireRequestNotification($lead));

            $lead->forceFill([
                'form_sent_at' => now(),
            ])->save();

            $this->record = $lead->refresh();

            Notification::make()
                ->title('Questionnaire sent')
                ->body('The questionnaire email was sent to '.$this->formatEmailRecipients($recipients).'.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Questionnaire could not be sent')
                ->body('Check the mail configuration and questionnaire email template, then try again.')
                ->danger()
                ->send();
        }
    }

    public function getQuestionMap(): array
    {
        return LeadQuestionnaire::byKey();
    }

    protected function emailRecipients(Lead $lead): array
    {
        if (blank($lead->email)) {
            return [];
        }

        return collect([
            $lead->email,
            $lead->secondary_email,
        ])
            ->map(fn (mixed $email): ?string => is_string($email) ? trim($email) : null)
            ->filter(fn (?string $email): bool => filled($email))
            ->unique(fn (string $email): string => mb_strtolower($email))
            ->values()
            ->all();
    }

    protected function formatEmailRecipients(array $recipients): string
    {
        if ($recipients === []) {
            return 'the client email';
        }

        if (count($recipients) === 1) {
            return $recipients[0];
        }

        $lastRecipient = array_pop($recipients);

        return implode(', ', $recipients).' and '.$lastRecipient;
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
