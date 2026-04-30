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
use Filament\Actions\ViewAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $pluralModelLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';
    protected static ?int $navigationSort = 2;

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
                    Components\TextInput::make('region')
                        ->label('Region')
                        ->maxLength(255),
                    Components\TextInput::make('locality')
                        ->label('Locality')
                        ->maxLength(255),
                    Components\DatePicker::make('event_start_date')
                        ->label('Event start date')
                        ->native(false),
                    Components\DatePicker::make('event_end_date')
                        ->label('Event end date')
                        ->native(false),
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
                TextColumn::make('event_start_date')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event_start_date', 'desc');
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
            'moodboard' => Pages\ViewProjectMoodboard::route('/{record}/moodboard'),
            'budget' => Pages\ViewProjectBudget::route('/{record}/budget'),
            'budget-scouting' => Pages\ManageProjectBudgetCategory::route('/{record}/budget/{categoryBudget}'),
            'budget-manage' => Pages\ManageProjectConfirmedSupplier::route('/{record}/budget/{categoryBudget}/manage'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
