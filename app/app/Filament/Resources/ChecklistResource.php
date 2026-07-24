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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
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
            View::make('filament.resources.checklist-resource.form-styles'),
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
                        ->label('Options')
                        ->extraAttributes(['class' => 'wm-checklist-options-repeater'])
                        ->columnSpanFull()
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'lg' => 12,
                            ])
                                ->schema([
                                    Components\TextInput::make('order')
                                        ->label('Order')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 1,
                                        ]),
                                    Components\RichEditor::make('title')
                                        ->label('Label')
                                        ->placeholder('Write the checklist instruction...')
                                        ->required()
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 11,
                                        ])
                                        ->maxLength(1000)
                                        ->toolbarButtons([
                                            'bold',
                                            'italic',
                                            'bulletList',
                                            'orderedList',
                                            'link',
                                            'undo',
                                            'redo',
                                        ]),
                                ])
                                ->columnSpanFull(),
                            Grid::make([
                                'default' => 1,
                                'lg' => 12,
                            ])
                                ->schema([
                                    Components\Select::make('assigned_to')
                                        ->label('Assigned to')
                                        ->options([
                                            'admin' => 'Admin',
                                            'client' => 'Client',
                                            'supplier' => 'Supplier',
                                            'none' => 'None',
                                        ])
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 3,
                                        ])
                                        ->required()
                                        ->default('none'),
                                    Components\TextInput::make('anticipation')
                                        ->label('Timing')
                                        ->placeholder('e.g. 3 days, 4 weeks, 9 months')
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 3,
                                        ])
                                        ->visible(fn (callable $get): bool => (bool) $get('default'))
                                        ->helperText('Only for default items.'),
                                    Components\Toggle::make('default')
                                        ->label('Default')
                                        ->default(false)
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 2,
                                        ])
                                        ->live(),
                                    Components\Toggle::make('to_be_filled')
                                        ->label('To be filled')
                                        ->default(false)
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 2,
                                        ])
                                        ->live()
                                        ->helperText('Requires a written response.'),
                                    Components\Toggle::make('insert_into_recap')
                                        ->label('Insert into recap')
                                        ->default(false)
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 2,
                                        ])
                                        ->visible(fn (callable $get): bool => (bool) $get('to_be_filled'))
                                        ->helperText('Show response in recap.'),
                                ])
                                ->columnSpanFull(),
                            Components\RichEditor::make('answer_template')
                                ->label('Answer template')
                                ->placeholder('Add a prefilled response structure...')
                                ->columnSpanFull()
                                ->visible(fn (callable $get): bool => (bool) $get('to_be_filled'))
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'bulletList',
                                    'orderedList',
                                    'link',
                                    'undo',
                                    'redo',
                                ]),
                        ])
                        ->columns(1)
                        ->defaultItems(0)
                        ->itemLabel(fn (?array $state): ?string => filled($state['title'] ?? null)
                            ? str((string) $state['title'])->stripTags()->squish()->limit(90)->toString()
                            : 'Checklist option')
                        ->collapsible()
                        ->reorderableWithButtons()
                        ->required(),
                ]),
        ]);
    }

    public static function normalizeOptionsForSave(array $data): array
    {
        $data['options'] = collect($data['options'] ?? [])
            ->map(function (array $option): array {
                $toBeFilled = (bool) ($option['to_be_filled'] ?? false);

                $option['insert_into_recap'] = $toBeFilled && (bool) ($option['insert_into_recap'] ?? false);
                $option['answer_template'] = $toBeFilled && filled($option['answer_template'] ?? null)
                    ? (string) $option['answer_template']
                    : null;

                return $option;
            })
            ->values()
            ->all();

        return $data;
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
