<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\LeadFollowUp;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    protected static ?string $title = 'Follow-ups';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    public function isReadOnly(): bool
    {
        return false;
    }

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
