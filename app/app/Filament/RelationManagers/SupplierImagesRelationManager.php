<?php

namespace App\Filament\RelationManagers;

use App\Models\SupplierImage;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Photogallery';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-photo';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Components\TextInput::make('title')
                ->maxLength(255),
            Components\Select::make('image_type')
                ->label('Type')
                ->options(SupplierImage::TYPE_OPTIONS)
                ->default('other')
                ->required(),
            Components\FileUpload::make('image_path')
                ->label('Image')
                ->disk('public')
                ->directory('suppliers/images')
                ->image()
                ->imageEditor()
                ->openable()
                ->downloadable()
                ->required(),
            Components\TextInput::make('sort_order')
                ->label('Sort order')
                ->numeric()
                ->default(0)
                ->minValue(0),
            Components\Toggle::make('is_client_visible')
                ->label('Client visible')
                ->default(false),
            Components\Textarea::make('description')
                ->rows(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->placeholder('Untitled')
                    ->searchable(),
                TextColumn::make('image_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => SupplierImage::TYPE_OPTIONS[$state] ?? (string) $state),
                IconColumn::make('is_client_visible')
                    ->label('Client visible')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
