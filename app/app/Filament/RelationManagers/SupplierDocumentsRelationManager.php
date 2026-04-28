<?php

namespace App\Filament\RelationManagers;

use App\Models\SupplierDocument;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-paper-clip';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Components\Select::make('document_type')
                ->label('Type')
                ->options(SupplierDocument::TYPE_OPTIONS)
                ->default('other')
                ->required(),
            Components\FileUpload::make('file_path')
                ->label('File')
                ->disk('public')
                ->directory('suppliers/documents')
                ->downloadable()
                ->openable()
                ->required(),
            Components\Textarea::make('description')
                ->rows(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge(),
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
