<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?string $pluralModelLabel = 'Suppliers';

    protected static ?string $modelLabel = 'Supplier';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 85;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('category_id')
                    ->orWhere('category_id', '!=', Supplier::LOCATION_CATEGORY_ID);
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(SupplierResourceSupport::baseSections());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Denomination')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.label_it')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_area')
                    ->label('Service area')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('City')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_person')
                    ->label('Contact')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('price_range')
                    ->label('Price range')
                    ->badge(),
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

    public static function getRelations(): array
    {
        return SupplierResourceSupport::assetRelations();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
