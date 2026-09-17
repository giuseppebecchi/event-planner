<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StrategicInfoResource\Pages;
use App\Models\StrategicInfo;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StrategicInfoResource extends Resource
{
    protected static ?string $model = StrategicInfo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'Strategic infos';

    protected static ?string $pluralModelLabel = 'Strategic infos';

    protected static ?string $modelLabel = 'Strategic info';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 82;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageUsers() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Strategic info')
                ->description('Configure an important item that must be completed for suppliers in a specific event category.')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),
                    Components\Select::make('category_id')
                        ->label('Event category')
                        ->relationship('category', 'label')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Components\TextInput::make('order')
                        ->label('Order')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(1),
                    Components\RichEditor::make('default_value')
                        ->label('Default value')
                        ->helperText('This content pre-fills the strategic info for projects. Existing customized content is never overwritten.')
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
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('order')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.label')
                    ->label('Event category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('default_value')
                    ->label('Default value')
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? str($state)->stripTags()->squish()->toString()
                        : '—')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategicInfos::route('/'),
            'create' => Pages\CreateStrategicInfo::route('/create'),
            'edit' => Pages\EditStrategicInfo::route('/{record}/edit'),
        ];
    }
}
