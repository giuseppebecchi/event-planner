<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadResource\Pages\Concerns\InteractsWithLeadPhaseContent;
use App\Models\Config;
use App\Models\Lead;
use App\Models\Template;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class BaseLeadPhasePage extends Page
{
    use InteractsWithLeadPhaseContent;
    use InteractsWithRecord;

    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.resources.lead-resource.pages.view-lead-phase';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return sprintf('%s %s', $this->getRecordTitle(), $this->getPhaseTitle());
    }

    public function getPhaseContentHtml(): string
    {
        return $this->renderConfigPlaceholders($this->getRawPhaseContentHtml());
    }

    protected function getRawPhaseContentHtml(): string
    {
        /** @var Lead $lead */
        $lead = $this->getRecord();
        $field = $this->getPhaseContentField();

        return (string) ($lead->{$field} ?? '');
    }

    public function getViewData(): array
    {
        return [
            'lead' => $this->getRecord()->loadMissing('signedContractDocument', 'project'),
            'phaseTitle' => $this->getPhaseTitle(),
            'phaseDescription' => $this->getPhaseDescription(),
            'phaseContentHtml' => $this->getPhaseContentHtml(),
            'phaseEmptyCopy' => $this->getPhaseEmptyCopy(),
            'asideData' => $this->getAsideData(),
            'exportPdfUrl' => $this->getExportPdfUrl(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getPhaseHeaderActions(),
        ];
    }

    public function generatePhaseAction(): Action
    {
        return Action::make('generatePhase')
            ->label($this->getGenerateActionLabel())
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(sprintf('Confirm %s creation', Str::lower($this->getPhaseTitle())))
            ->modalDescription(sprintf(
                'Confirm the creation of the %s? The current %s will be deleted.',
                Str::lower($this->getPhaseTitle()),
                Str::lower($this->getPhaseTitle()),
            ))
            ->action(fn () => $this->generatePhaseContentFromTemplate());
    }

    public function editContentAction(): Action
    {
        return Action::make('editContent')
            ->label('Edit content')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->fillForm(fn (): array => [
                'phaseContentDraft' => $this->getRawPhaseContentHtml(),
            ])
            ->form([
                RichEditor::make('phaseContentDraft')
                    ->label('HTML content')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'h2',
                        'h3',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'link',
                        'undo',
                        'redo',
                    ])
                    ->required()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $this->phaseContentDraft = (string) ($data['phaseContentDraft'] ?? '');
                $this->savePhaseContent();
            });
    }

    protected function generatePhaseContentFromTemplate(): void
    {
        /** @var Lead $lead */
        $lead = $this->getRecord()->loadMissing('project');

        $template = Template::query()
            ->where('slug', $this->getTemplateSlug())
            ->where('language', 'en')
            ->first();

        if (! $template) {
            Notification::make()
                ->title('Template not found')
                ->body(sprintf('No template with slug "%s" and language "en" was found.', $this->getTemplateSlug()))
                ->danger()
                ->send();

            return;
        }

        $content = $this->replaceTemplatePlaceholders((string) ($template->content ?? ''), $lead);

        $lead->forceFill([
            $this->getPhaseContentField() => trim($content) !== '' ? $content : null,
        ])->save();

        Notification::make()
            ->title(sprintf('%s generated', $this->getPhaseTitle()))
            ->success()
            ->send();
    }

    protected function getGenerateActionLabel(): string
    {
        return sprintf('Generate %s', Str::lower($this->getPhaseTitle()));
    }

    protected function getTemplateSlug(): string
    {
        return Str::of($this->getPhaseContentField())->before('_content')->value();
    }

    protected function replaceTemplatePlaceholders(string $content, Lead $lead): string
    {
        $project = $lead->project;

        $mainContactName = trim(collect([$lead->first_name, $lead->last_name])->filter()->implode(' '));
        $secondaryContactName = trim(collect([$lead->secondary_first_name, $lead->secondary_last_name])->filter()->implode(' '));

        $values = [
            'couple_name' => $lead->couple_name ?: trim(collect([$mainContactName, $secondaryContactName])->filter()->implode(' & ')),
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'nationality' => $lead->nationality,
            'city' => $lead->city,
            'secondary_first_name' => $lead->secondary_first_name,
            'secondary_last_name' => $lead->secondary_last_name,
            'secondary_email' => $lead->secondary_email,
            'secondary_phone' => $lead->secondary_phone,
            'estimated_guest_count' => $lead->estimated_guest_count,
            'wedding_period' => $lead->wedding_period,
            'wedding_date' => $lead->wedding_date ?: $lead->wedding_period,
            'desired_region' => $lead->desired_region,
            'ceremony_type' => $lead->ceremony_type,
            'ceremony_details' => $lead->ceremony_details,
            'location_request_type' => $lead->location_request_type,
            'venue' => $lead->venue,
            'ceremony_location' => $lead->ceremony_location,
            'estimated_timings' => $lead->estimated_timings,
            'additional_events' => $lead->additional_events,
            'budget_amount' => $this->formatMoney($lead->budget_amount),
            'style_description' => $lead->style_description,
            'proposal_sent_at' => $this->formatDateTime($lead->proposal_sent_at),
            'contract_sent_at' => $this->formatDateTime($lead->contract_sent_at) ?: now()->format('F jS Y'),
            'contract_received_at' => $this->formatDateTime($lead->contract_received_at),
            'internal_notes' => $lead->internal_notes,
            'name' => $project?->name,
            'project_first_name' => $project?->first_name,
            'project_last_name' => $project?->last_name,
            'project_email' => $project?->email,
            'project_phone' => $project?->phone,
            'project_city' => $project?->city,
            'project_secondary_first_name' => $project?->secondary_first_name,
            'project_secondary_last_name' => $project?->secondary_last_name,
            'project_secondary_email' => $project?->secondary_email,
            'project_secondary_phone' => $project?->secondary_phone,
            'partner_one_name' => $mainContactName,
            'partner_two_name' => $secondaryContactName,
            'reference_email' => $lead->email,
            'primary_phone' => $lead->phone,
            'address' => $lead->address,
            'private_notes' => $lead->internal_notes,
            'region' => $lead->desired_region,
            'locality' => $lead->venue ?: $lead->desired_region,
            'event_start_date' => $lead->wedding_date ?: $lead->wedding_period,
            'event_end_date' => null,
            'final_guest_count' => null,
            'status' => $project?->status,
            'reception_location' => $lead->venue,
            'proposal_valid_until' => now()->addDays(30)->format('F jS Y'),
            'contract_total_fee' => null,
            'contract_first_deposit' => null,
            'contract_second_deposit_due_at' => null,
            'contract_second_deposit' => null,
            'contract_balance_due_at' => null,
            'contract_balance' => null,
            'force_majeure_credit_until' => null,
        ];

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_-]+)\s*}}/', function (array $matches) use ($values): string {
            $key = $matches[1];
            $value = $values[$key] ?? null;

            if ($value === null || $value === '') {
                return $matches[0];
            }

            return (string) $value;
        }, $content) ?? $content;
    }

    protected function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('F jS Y');
    }

    protected function renderConfigPlaceholders(string $content): string
    {
        return preg_replace_callback('/{{\s*([a-zA-Z0-9_-]+)\s*}}/', function (array $matches): string {
            return $this->configPlaceholderHtml($matches[1]) ?? $matches[0];
        }, $content) ?? $content;
    }

    protected function configPlaceholderHtml(string $slug): ?string
    {
        $config = Config::query()
            ->where('slug', $slug)
            ->first();

        if (! $config) {
            return null;
        }

        if ($config->type === Config::TYPE_IMAGE && filled($config->img)) {
            $src = Storage::disk('public')->url((string) $config->img);

            return sprintf(
                '<img src="%s" alt="%s" style="max-width:220px;height:auto;">',
                e($src),
                e($config->label),
            );
        }

        if ($config->type === Config::TYPE_TEXT && filled($config->text)) {
            return e((string) $config->text);
        }

        return null;
    }

    protected function formatDateTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('F jS Y');
    }

    protected function formatMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', ',').' euros';
    }

    abstract protected function getPhaseTitle(): string;

    abstract protected function getPhaseDescription(): string;

    abstract protected function getPhaseEmptyCopy(): string;

    protected function getPhaseHeaderActions(): array
    {
        return [];
    }

    protected function getAsideData(): array
    {
        return [];
    }

    protected function getExportPdfUrl(): ?string
    {
        return null;
    }
}
