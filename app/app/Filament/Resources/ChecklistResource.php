<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistResource\Pages;
use App\Models\Checklist;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChecklistResource extends Resource
{
    protected static ?string $model = Checklist::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Checklists';

    protected static ?string $pluralModelLabel = 'Checklists';

    protected static ?string $modelLabel = 'Checklist';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 81;

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Checklist')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Components\TextInput::make('title')
                        ->columnSpan(1)
                        ->required()
                        ->maxLength(255),
                    Components\Select::make('category_id')
                        ->label('Supplier category')
                        ->relationship('category', 'label')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->columnSpan(1)
                        ->helperText('Leave empty for general checklists not tied to a supplier category.'),
                    Components\Repeater::make('options')
                        ->columnSpanFull()
                        ->schema([
                            Components\TextInput::make('order')
                                ->label('Order')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->columnSpan(1)
                                ->extraInputAttributes(['style' => 'max-width: 5.5rem;']),
                            Components\RichEditor::make('title')
                                ->required()
                                ->columnSpan(3)
                                ->maxLength(1000),
                            Components\Toggle::make('default')
                                ->default(false)
                                ->columnSpan(1)
                                ->live(),
                            Components\Toggle::make('to_be_filled')
                                ->label('To be filled')
                                ->default(false)
                                ->columnSpan(1)
                                ->helperText('Requires a written response instead of only a checkbox.'),
                            Components\Toggle::make('insert_into_recap')
                                ->label('Insert into recap')
                                ->default(false)
                                ->columnSpan(1)
                                ->helperText('Show the compiled text in the project recap.'),
                            Components\TextInput::make('anticipation')
                                ->placeholder('e.g. 3 days, 4 weeks, 9 months')
                                ->columnSpan(1)
                                ->visible(fn (callable $get): bool => (bool) $get('default'))
                                ->helperText('Only used for default checklist items.'),
                            Components\Select::make('assigned_to')
                                ->options([
                                    'admin' => 'Admin',
                                    'client' => 'Client',
                                    'supplier' => 'Supplier',
                                    'none' => 'None',
                                ])
                                ->columnSpan(2)
                                ->required()
                                ->default('none'),
                        ])
                        ->columns(10)
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.label')
                    ->label('Category')
                    ->placeholder('General')
                    ->sortable(),
                TextColumn::make('default_items_count')
                    ->label('Default items')
                    ->state(fn (Checklist $record): int => collect($record->options)->where('default', true)->count()),
                TextColumn::make('options_count')
                    ->label('Options')
                    ->state(fn (Checklist $record): int => count($record->options ?? [])),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecklists::route('/'),
            'create' => Pages\CreateChecklist::route('/create'),
            'edit' => Pages\EditChecklist::route('/{record}/edit'),
        ];
    }
}
