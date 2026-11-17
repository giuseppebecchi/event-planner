<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProposalTemplateResource\Pages;
use App\Models\ProposalTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProposalTemplateResource extends Resource
{
    protected static ?string $model = ProposalTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Proposal Templates';

    protected static ?int $navigationSort = 81;

    public static function canViewAny(): bool
    {
        return auth()->check() && ! auth()->user()->isCustomer();
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
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Template name')->required()->maxLength(255)->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                    if (blank($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')->label('Internal reference')->required()->maxLength(255)
                ->unique(ignoreRecord: true)->helperText('For example: tuscany-elopement'),
            Toggle::make('is_active')->label('Available for new proposals')->default(true),
            Select::make('base_template')->label('Start from')
                ->options(fn () => ProposalTemplate::orderBy('name')->pluck('name', 'id'))
                ->default(fn () => ProposalTemplate::where('slug', 'tuscany')->value('id') ?? ProposalTemplate::value('id'))
                ->visible(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (string $operation): bool => $operation === 'create')
                ->helperText('Copies the selected template. Text and photos can then be edited on the page preview.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('updated_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordUrl(fn (ProposalTemplate $record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([EditAction::make()->label('Settings')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposalTemplates::route('/'),
            'create' => Pages\CreateProposalTemplate::route('/create'),
            'view' => Pages\ViewProposalTemplate::route('/{record}'),
            'edit' => Pages\EditProposalTemplate::route('/{record}/edit'),
        ];
    }
}
