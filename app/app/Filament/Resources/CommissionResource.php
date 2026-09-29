<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Models\CategoryBudgetSupplier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommissionResource extends Resource
{
    protected static ?string $model = CategoryBudgetSupplier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Commissions';

    protected static ?string $pluralModelLabel = 'Commissions';

    protected static ?string $modelLabel = 'Commission';

    protected static string|\UnitEnum|null $navigationGroup = 'Manage';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isCustomer();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'project',
                'supplier',
                'category',
                'categoryBudget.category',
            ])
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)
            ->where('commission_mode', '!=', CategoryBudgetSupplier::COMMISSION_MODE_NONE)
            ->where('commission_amount', '>', 0)
            ->whereHas('project', fn (Builder $query): Builder => $query
                ->whereNotNull('event_date')
                ->whereDate('event_date', '<', today()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Event')
                    ->description(fn (CategoryBudgetSupplier $record): ?string => $record->project?->event_date?->format('d/m/Y'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->description(fn (CategoryBudgetSupplier $record): string => $record->categoryLabel())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('commission_mode')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CategoryBudgetSupplier::COMMISSION_MODE_OPTIONS[$state] ?? (string) $state),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('commission_total_amount_payed')
                    ->label('Paid')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('outstanding_commission')
                    ->label('Balance')
                    ->state(fn (CategoryBudgetSupplier $record): float => $record->commissionOutstandingAmount())
                    ->money('EUR'),
                TextColumn::make('payment_status')
                    ->label('Status')
                    ->state(fn (CategoryBudgetSupplier $record): string => $record->isCommissionPaid() ? 'Paid' : 'Unpaid')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Paid' ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'paid' => $query->whereColumn('commission_total_amount_payed', '>=', 'commission_amount'),
                            'unpaid' => $query->whereColumn('commission_total_amount_payed', '<', 'commission_amount'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Mark as paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark commission as paid')
                    ->modalDescription(fn (CategoryBudgetSupplier $record): string => sprintf(
                        'Register the outstanding balance of EUR %s as paid today?',
                        number_format($record->commissionOutstandingAmount(), 2, ',', '.'),
                    ))
                    ->visible(fn (CategoryBudgetSupplier $record): bool => ! $record->isCommissionPaid())
                    ->action(function (CategoryBudgetSupplier $record): void {
                        $record->markCommissionAsPaid();

                        Notification::make()
                            ->title('Commission marked as paid')
                            ->success()
                            ->send();
                    }),
                Action::make('openSupplier')
                    ->label('Open supplier')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CategoryBudgetSupplier $record): string => ProjectResource::getUrl('supplier-manage', [
                        'record' => $record->project_id,
                        'proposal' => $record->id,
                    ])),
            ])
            ->defaultSort('confirmed_at', 'desc')
            ->emptyStateHeading('No commissions for past events')
            ->emptyStateDescription('Commissions will appear here once an event date has passed.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
        ];
    }
}
