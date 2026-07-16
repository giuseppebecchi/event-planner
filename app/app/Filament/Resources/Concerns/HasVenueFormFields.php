<?php

namespace App\Filament\Resources\Concerns;

use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;

trait HasVenueFormFields
{
    protected static function venueSection(array $leadingFields = [], array $trailingFields = []): Section
    {
        return Section::make('Ceremony and venue')
            ->description('Ceremony format, accommodation and event flow.')
            ->icon('heroicon-o-building-library')
            ->columns(2)
            ->schema([
                ...$leadingFields,
                static::venueSelect(),
                static::createVenueButton(),
                static::legacyVenueField(),
                ...$trailingFields,
            ]);
    }

    protected static function venueSelect(): Components\Select
    {
        return Components\Select::make('venue_id')
            ->label('Venue/reception (already defined)')
            ->searchable()
            ->preload()
            ->getSearchResultsUsing(fn (?string $search): array => static::venueOptions($search))
            ->getOptionLabelUsing(fn ($value): ?string => static::venueOptionLabel($value));
    }

    protected static function createVenueButton(): SchemaActions
    {
        return SchemaActions::make([
            Action::make('createVenue')
                ->label('New Venue')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->modalHeading('New Venue')
                ->modalSubmitActionLabel('Create venue')
                ->schema(static::venueCreateOptionForm())
                ->action(function (array $data, Set $set): void {
                    $venue = static::createVenue($data);

                    $set('venue_id', $venue->getKey(), shouldCallUpdatedHooks: true);
                }),
        ]);
    }

    protected static function createVenue(array $data): Supplier
    {
        return Supplier::query()->create([
            ...$data,
            'category_id' => Supplier::LOCATION_CATEGORY_ID,
        ]);
    }

    protected static function legacyVenueField(): Components\TextInput
    {
        return Components\TextInput::make('venue')
            ->label('Deprecated venue/reception')
            ->helperText('Legacy text value. Use the venue selector above for new data.')
            ->maxLength(255)
            ->disabled()
            ->dehydrated()
            ->visible(fn (callable $get): bool => filled($get('venue')));
    }

    protected static function venueCreateOptionForm(): array
    {
        return [
            Components\TextInput::make('name')
                ->label('Venue name')
                ->required()
                ->maxLength(255),
            Components\TextInput::make('loc_locality')
                ->label('Locality')
                ->maxLength(255),
            Components\TextInput::make('city')
                ->label('City')
                ->maxLength(100),
            Components\TextInput::make('region')
                ->label('Region')
                ->maxLength(100),
            Components\Select::make('loc_structure_type')
                ->label('Structure type')
                ->options(Supplier::LOCATION_STRUCTURE_TYPES)
                ->searchable(),
            Components\Select::make('loc_style')
                ->label('Venue style')
                ->options(Supplier::LOCATION_STYLE_TYPES)
                ->searchable(),
            Components\TextInput::make('loc_website')
                ->label('Official website')
                ->url()
                ->maxLength(255),
            Components\TextInput::make('contact_person')
                ->label('Event contact')
                ->maxLength(255),
            Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            Components\TextInput::make('phone')
                ->label('Phone')
                ->tel()
                ->maxLength(50),
        ];
    }

    protected static function venueOptions(?string $search): array
    {
        return Supplier::query()
            ->where('category_id', Supplier::LOCATION_CATEGORY_ID)
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('loc_locality', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('region', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Supplier $venue): array => [$venue->getKey() => static::formatVenueOptionLabel($venue)])
            ->all();
    }

    protected static function venueOptionLabel($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $venue = Supplier::query()->find($value);

        return $venue ? static::formatVenueOptionLabel($venue) : null;
    }

    protected static function formatVenueOptionLabel(Supplier $venue): string
    {
        $place = collect([$venue->loc_locality, $venue->city, $venue->region])
            ->filter()
            ->unique()
            ->implode(', ');

        return $place ? "{$venue->name} ({$place})" : $venue->name;
    }
}
