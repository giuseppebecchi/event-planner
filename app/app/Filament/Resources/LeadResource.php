<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $recordTitleAttribute = 'couple_name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $pluralModelLabel = 'Leads';

    protected static ?string $modelLabel = 'Lead';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inquiry')
                ->description('Capture the first contact and qualification details.')
                ->icon('heroicon-o-sparkles')
                ->columns(3)
                ->schema([
                    Components\DatePicker::make('requested_at')
                        ->label('Inquiry date')
                        ->native(false),
                    Components\Select::make('source')
                        ->label('Source')
                        ->options(Lead::SOURCE_OPTIONS)
                        ->searchable(),
                    Components\TextInput::make('couple_name')
                        ->label('Couple name')
                        ->required()
                        ->maxLength(255),
                    Components\TextInput::make('budget_amount')
                        ->label('Estimated budget')
                        ->numeric()
                        ->prefix('EUR')
                        ->step(0.01)
                        ->minValue(0),
                    Components\Select::make('status')
                        ->label('Lead status')
                        ->options(Lead::STATUS_OPTIONS)
                        ->default('new')
                        ->required(),
                    Components\Select::make('evaluation_outcome')
                        ->label('Evaluation outcome')
                        ->options(Lead::EVALUATION_OUTCOME_OPTIONS)
                        ->default('maybe')
                        ->required(),
                    Components\TextInput::make('estimated_guest_count')
                        ->label('Estimated guests')
                        ->numeric()
                        ->minValue(0),
                    Components\TextInput::make('wedding_period')
                        ->label('Wedding period or month')
                        ->maxLength(255),
                    Components\TextInput::make('desired_region')
                        ->label('Preferred region')
                        ->maxLength(255),
                ]),

            Section::make('Contacts')
                ->description('Primary communication details for the couple.')
                ->icon('heroicon-o-envelope')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('first_name')
                        ->label('First name')
                        ->maxLength(255),
                    Components\TextInput::make('last_name')
                        ->label('Last name')
                        ->maxLength(255),
                    Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255)
                        ->columnSpan(1),
                    Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(50),
                    Components\TextInput::make('nationality')
                        ->label('Nationality')
                        ->maxLength(100),
                ]),

            Section::make('Ceremony and venue')
                ->description('Ceremony format, accommodation and event flow.')
                ->icon('heroicon-o-building-library')
                ->columns(2)
                ->schema([
                    Components\Select::make('ceremony_type')
                        ->label('Ceremony type')
                        ->options(Lead::CEREMONY_TYPE_OPTIONS),
                    Components\Select::make('location_request_type')
                        ->label('Venue request')
                        ->options(Lead::LOCATION_REQUEST_TYPE_OPTIONS),
                    Components\Textarea::make('ceremony_details')
                        ->label('Religious ceremony details / notes')
                        ->rows(4),
                    Components\Textarea::make('additional_events')
                        ->label('Events before / after the wedding')
                        ->rows(4),
                ]),

            Section::make('Style and internal notes')
                ->description('Moodboard direction, planner notes and internal context.')
                ->icon('heroicon-o-swatch')
                ->columns(2)
                ->schema([
                    Components\Textarea::make('style_description')
                        ->label('Wedding style')
                        ->rows(6)
                        ->columnSpanFull(),
                    Components\Textarea::make('internal_notes')
                        ->label('Internal notes')
                        ->rows(6)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('followUps')
                ->withCount([
                    'followUps as pending_follow_ups_count' => fn (Builder $followUpQuery): Builder => $followUpQuery->where('status', 'pending'),
                ])
                ->withMin([
                    'followUps as next_pending_follow_up_due_at' => fn (Builder $followUpQuery): Builder => $followUpQuery
                        ->where('status', 'pending')
                        ->whereNotNull('due_at'),
                ], 'due_at'))
            ->recordAction(null)
            ->recordUrl(fn (Lead $record): string => static::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('requested_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('couple_name')
                    ->label('Couple')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('First name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('last_name')
                    ->label('Last name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge(),
                TextColumn::make('desired_region')
                    ->label('Region')
                    ->searchable(),
                TextColumn::make('estimated_guest_count')
                    ->label('Guests')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('budget_amount')
                    ->label('Budget')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('follow_up_summary')
                    ->label('Follow up')
                    ->state(function (Lead $record): HtmlString {
                        $total = (int) ($record->follow_ups_count ?? 0);
                        $pending = (int) ($record->pending_follow_ups_count ?? 0);
                        $nextDueRaw = $record->next_pending_follow_up_due_at ?? null;

                        if ($total === 0) {
                            return new HtmlString(
                                '<div style="display:flex;flex-direction:column;gap:4px;">' .
                                '<span style="font-weight:700;color:#8c857e;">No follow up</span>' .
                                '<span style="font-size:12px;color:#a8a29e;">Nothing scheduled yet</span>' .
                                '</div>'
                            );
                        }

                        $chips = [];
                        $chips[] = '<span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;background:rgba(46,74,98,.10);color:#2E4A62;">' . $total . ' total</span>';
                        $chips[] = '<span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;background:' . ($pending > 0 ? 'rgba(122,143,123,.16);color:#617563;' : 'rgba(168,162,158,.16);color:#7b7570;') . '">' . $pending . ' pending</span>';

                        $meta = '<span style="font-size:12px;color:#8c857e;">No due date planned</span>';

                        if ($nextDueRaw) {
                            $nextDue = \Illuminate\Support\Carbon::parse($nextDueRaw);
                            $days = now()->startOfDay()->diffInDays($nextDue->startOfDay(), false);

                            if ($days < 0) {
                                $tone = 'background:rgba(227,183,178,.28);color:#8f5954;';
                                $label = 'Overdue by ' . abs($days) . 'd';
                            } elseif ($days === 0) {
                                $tone = 'background:rgba(201,169,106,.20);color:#8f6a2a;';
                                $label = 'Due today';
                            } elseif ($days <= 3) {
                                $tone = 'background:rgba(201,169,106,.18);color:#9a7a39;';
                                $label = 'Due in ' . $days . 'd';
                            } else {
                                $tone = 'background:rgba(46,74,98,.10);color:#2E4A62;';
                                $label = 'Next ' . $nextDue->format('d M');
                            }

                            $meta = '<span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;' . $tone . '">' . $label . '</span>';
                        }

                        return new HtmlString(
                            '<div style="display:flex;flex-direction:column;gap:6px;min-width:180px;">' .
                            '<div style="display:flex;flex-wrap:wrap;gap:6px;">' . implode('', $chips) . '</div>' .
                            '<div>' . $meta . '</div>' .
                            '</div>'
                        );
                    })
                    ->html(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('evaluation_outcome')
                    ->label('Match')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Lead::STATUS_OPTIONS),
                SelectFilter::make('source')
                    ->label('Source')
                    ->options(Lead::SOURCE_OPTIONS),
                SelectFilter::make('evaluation_outcome')
                    ->label('Match')
                    ->options(Lead::EVALUATION_OUTCOME_OPTIONS),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('requested_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inquiry')
                ->icon('heroicon-o-sparkles')
                ->columns(4)
                ->schema([
                    TextEntry::make('requested_at')
                        ->label('Inquiry date')
                        ->date('d/m/Y'),
                    TextEntry::make('source')
                        ->formatStateUsing(fn (?string $state): ?string => $state ? (Lead::SOURCE_OPTIONS[$state] ?? $state) : null)
                        ->badge(),
                    TextEntry::make('couple_name')
                        ->label('Couple'),
                    TextEntry::make('public_form_url')
                        ->label('Public form')
                        ->copyable()
                        ->copyMessage('Public form link copied')
                        ->url(fn (?string $state): ?string => $state, shouldOpenInNewTab: true)
                        ->columnSpanFull(),
                    TextEntry::make('budget_amount')
                        ->label('Budget')
                        ->money('EUR'),
                    TextEntry::make('status')
                        ->formatStateUsing(fn (?string $state): ?string => $state ? (Lead::STATUS_OPTIONS[$state] ?? $state) : null)
                        ->badge(),
                    TextEntry::make('evaluation_outcome')
                        ->label('Match')
                        ->formatStateUsing(fn (?string $state): ?string => $state ? (Lead::EVALUATION_OUTCOME_OPTIONS[$state] ?? $state) : null)
                        ->badge(),
                    TextEntry::make('estimated_guest_count')
                        ->label('Guests'),
                    TextEntry::make('desired_region')
                        ->label('Region'),
                ]),
            Section::make('Contacts')
                ->icon('heroicon-o-envelope')
                ->columns(4)
                ->schema([
                    TextEntry::make('first_name')
                        ->label('First name'),
                    TextEntry::make('last_name')
                        ->label('Last name'),
                    TextEntry::make('email')
                        ->label('Email'),
                    TextEntry::make('phone')
                        ->label('Phone'),
                    TextEntry::make('nationality')
                        ->label('Nationality'),
                    TextEntry::make('wedding_period')
                        ->label('Wedding period'),
                ]),
            Section::make('Ceremony and venue')
                ->icon('heroicon-o-building-library')
                ->columns(3)
                ->schema([
                    TextEntry::make('ceremony_type')
                        ->label('Ceremony type')
                        ->formatStateUsing(fn (?string $state): ?string => $state ? (Lead::CEREMONY_TYPE_OPTIONS[$state] ?? $state) : null),
                    TextEntry::make('location_request_type')
                        ->label('Venue request')
                        ->formatStateUsing(fn (?string $state): ?string => $state ? (Lead::LOCATION_REQUEST_TYPE_OPTIONS[$state] ?? $state) : null),
                    TextEntry::make('additional_events')
                        ->label('Additional events')
                        ->columnSpanFull(),
                    TextEntry::make('ceremony_details')
                        ->label('Ceremony details')
                        ->columnSpanFull(),
                ]),
            Section::make('Style and notes')
                ->icon('heroicon-o-swatch')
                ->columns(2)
                ->schema([
                    TextEntry::make('style_description')
                        ->label('Wedding style')
                        ->columnSpanFull(),
                    TextEntry::make('internal_notes')
                        ->label('Internal notes')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
            'documents' => Pages\ManageLeadDocuments::route('/{record}/documents'),
            'follow-ups' => Pages\ManageLeadFollowUps::route('/{record}/follow-ups'),
            'proposal' => Pages\ViewLeadProposal::route('/{record}/proposal'),
            'contract' => Pages\ViewLeadContract::route('/{record}/contract'),
            'form-data' => Pages\ViewLeadFormData::route('/{record}/form-data'),
            'budget-composition' => Pages\ViewLeadBudgetComposition::route('/{record}/budget-composition'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return [
            NavigationItem::make('Details')
                ->icon('heroicon-o-pencil-square')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.edit'))
                ->url(static::getUrl('edit', ['record' => $page->getRecord()])),
            NavigationItem::make('Documents')
                ->icon('heroicon-o-paper-clip')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.documents'))
                ->url(static::getUrl('documents', ['record' => $page->getRecord()])),
            NavigationItem::make('Follow up')
                ->icon('heroicon-o-clock')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.follow-ups'))
                ->url(static::getUrl('follow-ups', ['record' => $page->getRecord()])),
            NavigationItem::make('Questionnaire')
                ->icon('heroicon-o-document-text')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.form-data'))
                ->url(static::getUrl('form-data', ['record' => $page->getRecord()])),
            NavigationItem::make('Budget composition')
                ->icon('heroicon-o-banknotes')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.budget-composition'))
                ->url(static::getUrl('budget-composition', ['record' => $page->getRecord()])),
            NavigationItem::make('Proposal')
                ->icon('heroicon-o-document-check')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.proposal'))
                ->url(static::getUrl('proposal', ['record' => $page->getRecord()])),
            NavigationItem::make('Contract')
                ->icon('heroicon-o-document-text')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.contract'))
                ->url(static::getUrl('contract', ['record' => $page->getRecord()])),
        ];
    }
}
