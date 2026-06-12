<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $pluralModelLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';
    protected static ?int $navigationSort = 2;

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
            Section::make('Couple profile')
                ->columns(3)
                ->schema([
                    Components\Select::make('lead_id')
                        ->label('Related lead')
                        ->relationship('lead', 'couple_name')
                        ->searchable()
                        ->preload(),
                    Components\TextInput::make('name')
                        ->label('Event name')
                        ->required()
                        ->maxLength(255),
                    Components\Select::make('status')
                        ->label('Event status')
                        ->options(Project::STATUS_OPTIONS)
                        ->default('proposal')
                        ->required(),
                    Components\TextInput::make('partner_one_name')
                        ->label('Partner 1')
                        ->required()
                        ->maxLength(255),
                    Components\TextInput::make('partner_two_name')
                        ->label('Partner 2')
                        ->maxLength(255),
                    Components\TextInput::make('preferred_language')
                        ->label('Preferred language')
                        ->maxLength(100),
                    Components\TextInput::make('reference_email')
                        ->label('Reference email')
                        ->email()
                        ->maxLength(255),
                    Components\TextInput::make('partner_2_reference_email')
                        ->label('Partner 2 reference email')
                        ->email()
                        ->maxLength(255),
                    Components\TextInput::make('primary_phone')
                        ->label('Primary phone')
                        ->tel()
                        ->maxLength(50),
                    Components\TextInput::make('secondary_phone')
                        ->label('Secondary phone')
                        ->tel()
                        ->maxLength(50),
                    Components\TextInput::make('nationality')
                        ->label('Nationality')
                        ->maxLength(100),
                    Components\Textarea::make('address')
                        ->label('Address')
                        ->rows(4)
                        ->columnSpanFull(),
                    Components\Textarea::make('private_notes')
                        ->label('Private notes')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

            Section::make('Event')
                ->columns(3)
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Components\DatePicker::make('event_date')
                                ->label('Event date')
                                ->native(false)
                                ->required(),
                            Components\Checkbox::make('event_spans_multiple_days')
                                ->label('Event spans multiple days')
                                ->live(),
                            Components\DatePicker::make('event_start_date')
                                ->label('Start date')
                                ->visible(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                ->required(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                ->native(false),
                            Components\DatePicker::make('event_end_date')
                                ->label('End date')
                                ->visible(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                ->required(fn (callable $get): bool => (bool) $get('event_spans_multiple_days'))
                                ->afterOrEqual('event_start_date')
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
                    Components\FileUpload::make('cover_image_path')
                        ->label('RSVP cover image')
                        ->disk('public')
                        ->directory('projects/covers')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                    Components\Textarea::make('logistics_notes')
                        ->label('Logistics notes')
                        ->rows(6)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lead.couple_name')
                    ->label('Lead')
                    ->searchable(),
                TextColumn::make('partner_one_name')
                    ->label('Partner 1')
                    ->searchable(),
                TextColumn::make('partner_two_name')
                    ->label('Partner 2')
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
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event_date', 'desc');
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'checklist' => Pages\ViewProjectChecklist::route('/{record}/checklist'),
            'calendar' => Pages\ViewProjectCalendar::route('/{record}/calendar'),
            'timeline' => Pages\ViewProjectTimeline::route('/{record}/timeline'),
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
            'supplier-manage' => Pages\ManageProjectSupplier::route('/{record}/suppliers/{proposal}/manage'),
            'budget-manage' => Pages\ManageProjectConfirmedSupplier::route('/{record}/budget/{categoryBudget}/manage'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
