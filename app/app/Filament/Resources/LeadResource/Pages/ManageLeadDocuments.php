<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\LeadDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ManageLeadDocuments extends ManageRelatedRecords
{
    protected static string $resource = LeadResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $breadcrumb = 'Documents';

    protected static ?string $navigationLabel = 'Documents';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Components\Select::make('document_type')
                ->options(LeadDocument::TYPE_OPTIONS)
                ->default('brochure')
                ->required(),
            Components\FileUpload::make('file_path')
                ->label('File')
                ->disk('public')
                ->directory('leads/documents')
                ->acceptedFileTypes([
                    'application/pdf',
                ])
                ->maxSize(20480)
                ->downloadable()
                ->openable()
                ->helperText('PDF only, up to 20 MB.')
                ->required(),
            Components\DateTimePicker::make('uploaded_at')
                ->default(now()),
            Components\Toggle::make('is_shared_with_client')
                ->label('Shared with client'),
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
                    ->searchable()
                    ->url(fn (LeadDocument $record): string => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn (): string => 'Open PDF')
                    ->color('primary')
                    ->url(fn (LeadDocument $record): string => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),
                IconColumn::make('is_shared_with_client')
                    ->label('Shared')
                    ->boolean(),
                TextColumn::make('uploaded_at')
                    ->label('Uploaded')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LeadDocument $record): string => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
