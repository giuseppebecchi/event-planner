<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\Concerns\HasVenueFormFields;
use App\Models\Lead;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class ProjectResource extends Resource
{
    use HasVenueFormFields;

    protected static ?string $model = Project::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $pluralModelLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user?->isCustomer()) {
            return true;
        }

        return $user->customerProjectsCount() !== 1;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isCustomer()) {
            return $query->whereHas('users', fn (Builder $query): Builder => $query->whereKey($user->id));
        }

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canEdit(Model $record): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canDelete(Model $record): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canDeleteAny(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canRestore(Model $record): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canRestoreAny(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canForceDelete(Model $record): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canForceDeleteAny(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'default' => 1,
                'xl' => 2,
            ])
                ->columnSpanFull()
                ->schema([
                    Group::make()
                        ->columnSpan([
                            'default' => 1,
                            'xl' => 1,
                        ])
                        ->schema([
                            Section::make('Event info')
                                ->columns(4)
                                ->schema([
                                    Components\TextInput::make('name')
                                        ->label('Event name')
                                        ->required()
                                        ->maxLength(255),
                                    Components\Select::make('status')
                                        ->label('Event status')
                                        ->options(Project::STATUS_OPTIONS)
                                        ->default('proposal')
                                        ->required(),
                                    Components\Select::make('preferred_language')
                                        ->label('Language')
                                        ->options([
                                            'English' => 'English',
                                            'Italian' => 'Italian',
                                            'French' => 'French',
                                        ])
                                        ->searchable(),
                                    Components\Select::make('lead_id')
                                        ->label('Related lead')
                                        ->relationship('lead', 'couple_name')
                                        ->searchable()
                                        ->preload(),
                                    Grid::make(4)
                                        ->schema([
                                            Components\DatePicker::make('event_date')
                                                ->label('Event date')
                                                ->native(false),
                                            Components\Select::make('time_format')
                                                ->label('Time format')
                                                ->options(Project::TIME_FORMAT_OPTIONS)
                                                ->default('12h')
                                                ->required(),
                                            Components\TextInput::make('wedding_period')
                                                ->label('Wedding period or month')
                                                ->maxLength(255),
                                            Components\Checkbox::make('event_spans_multiple_days')
                                                ->label('Event spans multiple days')
                                                ->live(),
                                            Components\DatePicker::make('event_start_date')
                                                ->label('Start date')
                                                ->visible(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                                ->required(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                                ->live()
                                                ->native(false),
                                            Components\DatePicker::make('event_end_date')
                                                ->label('End date')
                                                ->visible(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                                ->required(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                                ->afterOrEqual('event_start_date')
                                                ->minDate(fn (callable $get): ?string => filled($get('event_start_date')) ? (string) $get('event_start_date') : null)
                                                ->defaultFocusedDate(fn (callable $get): ?string => filled($get('event_start_date')) ? Carbon::parse($get('event_start_date'))->addDay()->toDateString() : null)
                                                ->native(false),
                                        ])
                                        ->columnSpanFull(),
                                    Components\TextInput::make('region')
                                        ->label('Region')
                                        ->maxLength(255),
                                    Components\TextInput::make('locality')
                                        ->label('Locality')
                                        ->maxLength(255),
                                    Components\TextInput::make('estimated_guest_count')
                                        ->label('Estimated guests')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\TextInput::make('final_guest_count')
                                        ->label('Final guest count')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\TextInput::make('budget_amount')
                                        ->label('Couple budget')
                                        ->numeric()
                                        ->prefix('EUR')
                                        ->step('0.01')
                                        ->minValue(0),
                                    Components\Toggle::make('venue_included_in_budget')
                                        ->label('Venue included in budget'),
                                    Components\TextInput::make('logistics_notes')
                                        ->label('Logistics notes')
                                        ->columnSpan(2),
                                ]),

                            static::venueSection(
                                leadingFields: [
                                    Components\Select::make('ceremony_type')
                                        ->label('Ceremony type')
                                        ->options(Lead::CEREMONY_TYPE_OPTIONS),
                                    Components\Select::make('location_request_type')
                                        ->label('Venue request')
                                        ->options(Lead::LOCATION_REQUEST_TYPE_OPTIONS),
                                ],
                                trailingFields: [
                                    Components\TextInput::make('estimated_timings')
                                        ->label('Estimated timings')
                                        ->maxLength(255),
                                    Components\TextInput::make('ceremony_location')
                                        ->label('Ceremony location')
                                        ->maxLength(255),
                                    Components\TextInput::make('ceremony_details')
                                        ->label('Religious ceremony details / notes')
                                        ->maxLength(255),
                                    Components\TextInput::make('additional_events')
                                        ->label('Events before / after the wedding')
                                        ->maxLength(255),
                                ],
                            ),
                        ]),

                    Group::make()
                        ->columnSpan([
                            'default' => 1,
                            'xl' => 1,
                        ])
                        ->schema([
                            Section::make('Main Contact')
                                ->description('Primary communication details for the couple.')
                                ->icon('heroicon-o-envelope')
                                ->columns(4)
                                ->schema([
                                    Components\TextInput::make('first_name')
                                        ->label('First name')
                                        ->maxLength(255),
                                    Components\TextInput::make('last_name')
                                        ->label('Last name')
                                        ->required()
                                        ->maxLength(255),
                                    Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(255),
                                    Components\TextInput::make('phone')
                                        ->label('Phone')
                                        ->tel()
                                        ->maxLength(50),
                                    Components\TextInput::make('nationality')
                                        ->label('Nationality')
                                        ->maxLength(100),
                                    Components\TextInput::make('city')
                                        ->label('City')
                                        ->maxLength(255),
                                    Components\TextInput::make('address')
                                        ->label('Address')
                                        ->columnSpan(2),
                                ]),
                            Section::make('Partner Contact')
                                ->description('Secondary communication details for the partner.')
                                ->icon('heroicon-o-user-plus')
                                ->columns(4)
                                ->schema([
                                    Components\TextInput::make('secondary_first_name')
                                        ->label('First name')
                                        ->maxLength(255),
                                    Components\TextInput::make('secondary_last_name')
                                        ->label('Last name')
                                        ->maxLength(255),
                                    Components\TextInput::make('secondary_email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(255),
                                    Components\TextInput::make('secondary_phone')
                                        ->label('Phone')
                                        ->tel()
                                        ->maxLength(50),
                                ]),
                            Section::make('Style and internal notes')
                                ->description('Moodboard direction, planner notes and internal context.')
                                ->icon('heroicon-o-swatch')
                                ->columns(2)
                                ->schema([
                                    Components\TextInput::make('style_description')
                                        ->label('Wedding style')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Components\Textarea::make('internal_notes')
                                        ->label('Internal notes')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                            View::make('filament.resources.project-resource.pages.partials.customer-credentials')
                                ->visible(fn (string $operation): bool => $operation === 'edit'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lead.couple_name')
                    ->label('Lead')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Partner 1')
                    ->state(fn (Project $record): string => $record->mainContactName())
                    ->searchable(),
                TextColumn::make('secondary_last_name')
                    ->label('Partner 2')
                    ->state(fn (Project $record): string => $record->secondaryContactName())
                    ->searchable(),
                TextColumn::make('region')
                    ->label('Region')
                    ->searchable(),
                TextColumn::make('event_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estimated_guest_count')
                    ->label('Estimated guests')
                    ->numeric(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Project::STATUS_OPTIONS),
                SelectFilter::make('lead_id')
                    ->label('Lead')
                    ->options(fn (): array => Lead::query()->orderBy('couple_name')->pluck('couple_name', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (): bool => ! auth()->user()?->isCustomer()),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ])->visible(fn (): bool => ! auth()->user()?->isCustomer()),
            ])
            ->defaultSort('event_date', 'asc');
    }

    public static function normalizeEventDateFormData(array $data): array
    {
        $eventDate = $data['event_date'] ?? null;
        $spansMultipleDays = (bool) ($data['event_spans_multiple_days'] ?? false);

        if ($spansMultipleDays) {
            $data['event_start_date'] = filled($data['event_start_date'] ?? null) ? $data['event_start_date'] : $eventDate;
            $data['event_end_date'] = filled($data['event_end_date'] ?? null) ? $data['event_end_date'] : $data['event_start_date'];
        } else {
            $data['event_start_date'] = $eventDate;
            $data['event_end_date'] = $eventDate;
        }

        unset($data['event_spans_multiple_days']);

        return $data;
    }

    public static function eventSpansMultipleDaysFromFormData(array $data): bool
    {
        if (blank($data['event_start_date'] ?? null) || blank($data['event_end_date'] ?? null)) {
            return false;
        }

        return ! Carbon::parse($data['event_start_date'])->isSameDay(Carbon::parse($data['event_end_date']));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'checklist' => Pages\ViewProjectChecklist::route('/{record}/checklist'),
            'calendar' => Pages\ViewProjectCalendar::route('/{record}/calendar'),
            'timeline' => Pages\ViewProjectTimeline::route('/{record}/timeline'),
            'recap' => Pages\ViewProjectRecap::route('/{record}/recap'),
            'layouts' => Pages\ViewProjectLayouts::route('/{record}/layouts'),
            'website' => Pages\ManageProjectWebsite::route('/{record}/website'),
            'layout-edit' => Pages\EditProjectLayout::route('/{record}/layouts/{seatingPlan}'),
            'layout-assign' => Pages\AssignProjectLayout::route('/{record}/layouts/{seatingPlan}/assign'),
            'moodboard' => Pages\ViewProjectMoodboard::route('/{record}/moodboard'),
            'guests' => Pages\ViewProjectGuests::route('/{record}/guests'),
            'guests-rsvp-configuration' => Pages\ManageProjectRsvpConfiguration::route('/{record}/guests/rsvp-configuration'),
            'guests-rsvp-responses' => Pages\ViewProjectRsvpResponses::route('/{record}/guests/rsvp-responses'),
            'budget' => Pages\ViewProjectBudget::route('/{record}/budget'),
            'budget-scouting' => Pages\ManageProjectBudgetCategory::route('/{record}/budget/{categoryBudget}'),
            'suppliers' => Pages\ViewProjectSuppliers::route('/{record}/suppliers'),
            'documents' => Pages\ViewProjectDocuments::route('/{record}/documents'),
            'supplier-manage' => Pages\ManageProjectSupplier::route('/{record}/suppliers/{proposal}/manage'),
            'budget-manage' => Pages\ManageProjectConfirmedSupplier::route('/{record}/budget/{categoryBudget}/manage'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
