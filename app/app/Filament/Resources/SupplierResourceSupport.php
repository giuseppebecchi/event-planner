<?php

namespace App\Filament\Resources;

use App\Filament\RelationManagers\SupplierDocumentsRelationManager;
use App\Filament\RelationManagers\SupplierImagesRelationManager;
use App\Models\Category;
use App\Models\Supplier;
use Filament\Forms\Components;
use Filament\Schemas\Components\Section;

class SupplierResourceSupport
{
    public static function assetRelations(): array
    {
        return [
            SupplierDocumentsRelationManager::class,
            SupplierImagesRelationManager::class,
        ];
    }

    public static function baseSections(bool $excludeLocationCategory = true): array
    {
        return [
            ...static::mainAndAddressSections($excludeLocationCategory),

            Section::make('Billing')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('vat_number')
                        ->label('VAT number')
                        ->maxLength(50),
                    Components\TextInput::make('tax_code')
                        ->label('Tax code')
                        ->maxLength(50),
                    Components\TextInput::make('sdi_code')
                        ->label('SDI')
                        ->maxLength(50),
                ]),

            Section::make('Notes')
                ->schema([
                    Components\Textarea::make('internal_notes')
                        ->label('Internal notes')
                        ->rows(6),
                ]),
        ];
    }

    public static function mainAndAddressSections(bool $excludeLocationCategory = true, bool $includeCategoryField = true): array
    {
        $mainInformationFields = [
            Components\TextInput::make('name')
                ->label('Denomination')
                ->required()
                ->maxLength(255),
        ];

        if ($includeCategoryField) {
            $mainInformationFields[] = Components\Select::make('category_id')
                ->label('Service category')
                ->options(fn (): array => Category::query()
                    ->when(
                        $excludeLocationCategory,
                        fn ($query) => $query->where('id', '!=', Supplier::LOCATION_CATEGORY_ID)
                    )
                    ->pluck('label_it', 'id')
                    ->all())
                ->searchable()
                ->preload();
        }

        $mainInformationFields = [
            ...$mainInformationFields,
            Components\TextInput::make('service_area')
                ->label('Service area')
                ->maxLength(255),
            Components\TextInput::make('location')
                ->label('Location')
                ->maxLength(255),
            Components\TextInput::make('contact_person')
                ->label('Contact person')
                ->maxLength(255),
            Components\TextInput::make('price_range')
                ->label('Price range')
                ->maxLength(255),
            Components\TextInput::make('email')
                ->email()
                ->maxLength(255),
            Components\TextInput::make('phone')
                ->tel()
                ->maxLength(50),
            Components\TextInput::make('style_description')
                ->label('Description / style')
                ->maxLength(255)
                ->columnSpan(2),
        ];

        return [
            Section::make('Main information')
                ->columns(3)
                ->schema($mainInformationFields),

            Section::make('Address')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('address_line_1')
                        ->label('Address line 1')
                        ->maxLength(255)
                        ->columnSpan(2),
                    Components\TextInput::make('address_line_2')
                        ->label('Address line 2')
                        ->maxLength(255),
                    Components\TextInput::make('postal_code')
                        ->label('Postal code')
                        ->maxLength(20),
                    Components\TextInput::make('city')
                        ->label('City')
                        ->maxLength(100),
                    Components\TextInput::make('province')
                        ->label('Province')
                        ->maxLength(100),
                    Components\TextInput::make('region')
                        ->label('Region')
                        ->maxLength(100),
                    Components\TextInput::make('country')
                        ->label('Country')
                        ->maxLength(100),
                ]),
        ];
    }
}
