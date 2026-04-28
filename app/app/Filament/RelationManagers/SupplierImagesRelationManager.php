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
            Components\FileUpload::make('image_path')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('suppliers/images')
                ->openable()
                ->downloadable()
                ->required(),
            Components\Select::make('image_category')
                ->label('Category')
                ->options(SupplierImage::CATEGORY_OPTIONS)
                ->default('other')
                ->required(),
            Components\Toggle::make('is_client_visible')
                ->label('Visible in client presentations'),
            Components\Textarea::make('description')
                ->rows(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('image_category')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->square(),
                TextColumn::make('image_category')
                    ->label('Category')
                    ->badge(),
                TextColumn::make('description')
                    ->limit(40)
                    ->wrap(),
                IconColumn::make('is_client_visible')
                    ->label('Client use')
                    ->boolean(),
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
