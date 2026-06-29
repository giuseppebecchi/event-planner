<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigResource\Pages;
use App\Models\Config;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ConfigResource extends Resource
{
    protected static ?string $model = Config::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configs';

    protected static ?string $pluralModelLabel = 'Configs';

    protected static ?string $modelLabel = 'Config';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 83;

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Config')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Components\TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                            if (filled($get('slug'))) {
                                return;
                            }

                            $set('slug', Str::slug((string) $state));
                        }),
                    Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Components\Select::make('type')
                        ->required()
                        ->default(Config::TYPE_TEXT)
                        ->options(Config::TYPE_OPTIONS)
                        ->native(false)
                        ->live(),
                    Components\Textarea::make('text')
                        ->label('Text')
                        ->rows(8)
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => $get('type') === Config::TYPE_TEXT),
                    Components\FileUpload::make('img')
                        ->label('Image')
                        ->disk('public')
                        ->directory('configs')
                        ->image()
                        ->imagePreviewHeight('120')
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => $get('type') === Config::TYPE_IMAGE),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('label')
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Config::TYPE_OPTIONS[$state] ?? $state),
                TextColumn::make('text')
                    ->limit(80)
                    ->toggleable(),
                ImageColumn::make('img')
                    ->label('Image')
                    ->disk('public')
                    ->height(48)
                    ->toggleable(),
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
            'index' => Pages\ListConfigs::route('/'),
            'create' => Pages\CreateConfig::route('/create'),
            'edit' => Pages\EditConfig::route('/{record}/edit'),
        ];
    }
}
