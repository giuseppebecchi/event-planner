<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\LeadFollowUp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageLeadFollowUps extends ManageRelatedRecords
{
    protected static string $resource = LeadResource::class;

    protected static string $relationship = 'followUps';

    protected static ?string $breadcrumb = 'Follow up';

    protected static ?string $navigationLabel = 'Follow up';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Components\TextInput::make('subject')
                ->required()
                ->maxLength(255),
            Components\Select::make('follow_up_type')
                ->label('Type')
                ->options(LeadFollowUp::TYPE_OPTIONS)
                ->default('generic')
                ->required(),
            Components\Select::make('status')
                ->options(LeadFollowUp::STATUS_OPTIONS)
                ->default('pending')
                ->required(),
            Components\Select::make('priority')
                ->options(LeadFollowUp::PRIORITY_OPTIONS)
                ->default('normal')
                ->required(),
            Components\DateTimePicker::make('due_at')
                ->label('Due at'),
            Components\DateTimePicker::make('remind_at')
                ->label('Remind at'),
            Components\DateTimePicker::make('completed_at')
                ->label('Completed at'),
            Components\Select::make('outcome')
                ->options(LeadFollowUp::OUTCOME_OPTIONS),
            Components\Textarea::make('notes')
                ->rows(5)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->recordAction(EditAction::class)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('follow_up_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('priority')
                    ->badge(),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('outcome')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
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
}
