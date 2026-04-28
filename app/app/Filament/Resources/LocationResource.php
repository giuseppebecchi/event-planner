<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Locations';

    protected static ?string $pluralModelLabel = 'Locations';

    protected static ?string $modelLabel = 'Location';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 84;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('category_id', Supplier::LOCATION_CATEGORY_ID);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Components\Hidden::make('category_id')
                    ->default(Supplier::LOCATION_CATEGORY_ID),
                Tabs::make('Location tabs')
                    ->contained(false)
                    ->columnSpanFull()
                    ->tabs([
                    Tab::make('Overview')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Section::make('Main information')
                                ->columns(3)
                                ->schema([
                                    Components\TextInput::make('name')
                                        ->label('Location name')
                                        ->required()
                                        ->maxLength(255),
                                    Components\TextInput::make('loc_locality')
                                        ->label('Locality')
                                        ->maxLength(255),
                                    Components\Select::make('loc_structure_type')
                                        ->label('Structure type')
                                        ->options(Supplier::LOCATION_STRUCTURE_TYPES)
                                        ->searchable(),
                                    Components\Select::make('loc_style')
                                        ->label('Location style')
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
                                    Components\Textarea::make('loc_overview')
                                        ->label('General description')
                                        ->rows(5)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Address')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
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
                                    Components\Select::make('loc_geo_area')
                                        ->label('Geographic area')
                                        ->options([
                                            'Toscana' => 'Toscana',
                                            'Lago di Como' => 'Lago di Como',
                                            'Umbria' => 'Umbria',
                                        ])
                                        ->searchable(),
                                    Components\TextInput::make('loc_latitude')
                                        ->label('Latitude')
                                        ->numeric()
                                        ->step('0.0000001'),
                                    Components\TextInput::make('loc_longitude')
                                        ->label('Longitude')
                                        ->numeric()
                                        ->step('0.0000001'),
                                    Components\TextInput::make('loc_airport_distance_km')
                                        ->label('Nearest airport distance (km)')
                                        ->numeric()
                                        ->step('0.01'),
                                ]),
                        ]),
                    Tab::make('Capacity')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Section::make('Capacity and spaces')
                                ->columns(4)
                                ->schema([
                                    Components\TextInput::make('loc_guest_max')
                                        ->label('Max guests')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\TextInput::make('loc_guest_indoor_max')
                                        ->label('Indoor max guests')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\TextInput::make('loc_guest_outdoor_max')
                                        ->label('Outdoor max guests')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\TextInput::make('loc_guest_min')
                                        ->label('Min guests')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\Toggle::make('loc_has_garden')
                                        ->label('Garden / outdoor spaces'),
                                    Components\Toggle::make('loc_has_indoor_hall')
                                        ->label('Indoor hall / rain plan'),
                                    Components\Toggle::make('loc_has_ceremony_space')
                                        ->label('Dedicated ceremony spaces'),
                                    Components\Textarea::make('loc_event_spaces')
                                        ->label('Event and reception spaces')
                                        ->rows(4)
                                        ->columnSpan(2),
                                    Components\Textarea::make('loc_other_event_areas')
                                        ->label('Other event areas')
                                        ->rows(4)
                                        ->columnSpan(2),
                                ]),
                        ]),
                    Tab::make('Hospitality')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Section::make('Rooms and accommodation')
                                ->columns(4)
                                ->schema([
                                    Components\Toggle::make('loc_has_rooms')
                                        ->label('Rooms available'),
                                    Components\TextInput::make('loc_room_count')
                                        ->label('Room count')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\TextInput::make('loc_stay_guest_max')
                                        ->label('Max overnight guests')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\Toggle::make('loc_exclusive_use')
                                        ->label('Exclusive use available'),
                                    Components\TextInput::make('loc_min_nights')
                                        ->label('Minimum nights')
                                        ->numeric()
                                        ->minValue(0),
                                    Components\Textarea::make('loc_room_setup')
                                        ->label('Room setup')
                                        ->rows(4)
                                        ->columnSpan(2),
                                    Components\Textarea::make('loc_stay_notes')
                                        ->label('Stay notes')
                                        ->rows(4)
                                        ->columnSpan(2),
                                ]),
                        ]),
                    Tab::make('Catering')
                        ->icon('heroicon-o-cake')
                        ->schema([
                            Section::make('Catering')
                                ->columns(3)
                                ->schema([
                                    Components\Select::make('loc_catering_type')
                                        ->label('Catering type')
                                        ->options(Supplier::LOCATION_CATERING_TYPES)
                                        ->searchable(),
                                    Components\Toggle::make('loc_has_inhouse_catering')
                                        ->label('In-house catering'),
                                    Components\Toggle::make('loc_allows_external_catering')
                                        ->label('External catering allowed'),
                                    Components\Textarea::make('loc_exclusive_caterers')
                                        ->label('Exclusive caterers')
                                        ->rows(4),
                                    Components\Textarea::make('loc_external_catering_rules')
                                        ->label('External catering conditions')
                                        ->rows(4),
                                    Components\Textarea::make('loc_catering_notes')
                                        ->label('Operational catering notes')
                                        ->rows(4),
                                ]),
                        ]),
                    Tab::make('Ceremonies')
                        ->icon('heroicon-o-heart')
                        ->schema([
                            Section::make('Ceremonies')
                                ->columns(3)
                                ->schema([
                                    Components\Select::make('loc_ceremony_types')
                                        ->label('Ceremony types')
                                        ->multiple()
                                        ->options(Supplier::LOCATION_CEREMONY_TYPES)
                                        ->searchable(),
                                    Components\Toggle::make('loc_allows_ceremony_on_site')
                                        ->label('Ceremony on site'),
                                    Components\Textarea::make('loc_ceremony_spaces')
                                        ->label('Ceremony spaces')
                                        ->rows(4),
                                    Components\Textarea::make('loc_ceremony_rules')
                                        ->label('Ceremony limits and conditions')
                                        ->rows(4)
                                        ->columnSpan(2),
                                ]),
                        ]),
                    Tab::make('Operations')
                        ->icon('heroicon-o-speaker-wave')
                        ->schema([
                            Section::make('Music and sound')
                                ->columns(3)
                                ->schema([
                                    Components\TimePicker::make('loc_music_end_time')
                                        ->label('Music end time')
                                        ->seconds(false),
                                    Components\Toggle::make('loc_music_extension')
                                        ->label('Music extension possible'),
                                    Components\Textarea::make('loc_sound_limits')
                                        ->label('Sound limits')
                                        ->rows(4),
                                    Components\Textarea::make('loc_music_rules')
                                        ->label('DJ / live music conditions')
                                        ->rows(4),
                                    Components\Textarea::make('loc_music_notes')
                                        ->label('Music notes')
                                        ->rows(4),
                                ]),
                            Section::make('Fireworks')
                                ->columns(3)
                                ->schema([
                                    Components\Toggle::make('loc_allows_fireworks')
                                        ->label('Fireworks allowed'),
                                    Components\TextInput::make('loc_fireworks_area')
                                        ->label('Dedicated fireworks area')
                                        ->maxLength(255),
                                    Components\Textarea::make('loc_fireworks_rules')
                                        ->label('Fireworks restrictions')
                                        ->rows(4),
                                    Components\Textarea::make('loc_fireworks_permits')
                                        ->label('Permits and authorizations')
                                        ->rows(4)
                                        ->columnSpan(2),
                                ]),
                            Section::make('Logistics and operational limits')
                                ->columns(3)
                                ->schema([
                                    Components\Textarea::make('loc_supplier_access')
                                        ->label('Supplier and technical access')
                                        ->rows(4),
                                    Components\Toggle::make('loc_has_parking')
                                        ->label('Parking available'),
                                    Components\Toggle::make('loc_accessible')
                                        ->label('Accessible for reduced mobility'),
                                    Components\Textarea::make('loc_protected_areas')
                                        ->label('Protected / non-walkable areas')
                                        ->rows(4),
                                    Components\Textarea::make('loc_setup_limits')
                                        ->label('Setup limitations')
                                        ->rows(4),
                                    Components\Textarea::make('loc_setup_time_limits')
                                        ->label('Setup / teardown time limits')
                                        ->rows(4),
                                    Components\Textarea::make('loc_other_limits')
                                        ->label('Other operational restrictions')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Costs')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Indicative costs')
                                ->columns(3)
                                ->schema([
                                    Components\TextInput::make('loc_rental_fee')
                                        ->label('Indicative rental fee')
                                        ->numeric()
                                        ->prefix('EUR')
                                        ->step('0.01'),
                                    Components\Select::make('loc_rental_mode')
                                        ->label('Rental mode')
                                        ->options(Supplier::LOCATION_RENTAL_MODES),
                                    Components\TextInput::make('loc_booking_deposit')
                                        ->label('Booking deposit')
                                        ->numeric()
                                        ->prefix('EUR')
                                        ->step('0.01'),
                                    Components\Textarea::make('loc_extra_costs')
                                        ->label('Recurring extra costs')
                                        ->rows(4),
                                    Components\Textarea::make('loc_payment_terms')
                                        ->label('Payment terms and timing')
                                        ->rows(4)
                                        ->columnSpan(2),
                                ]),
                        ]),
                    Tab::make('Admin')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Billing and planner notes')
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
                                    Components\Textarea::make('internal_notes')
                                        ->label('Wedding planner notes')
                                        ->rows(6)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('loc_locality')
                    ->label('Locality')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('loc_geo_area')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('loc_structure_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('loc_guest_max')
                    ->label('Guests')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('loc_catering_type')
                    ->label('Catering')
                    ->badge(),
                IconColumn::make('loc_has_rooms')
                    ->label('Rooms')
                    ->boolean(),
                IconColumn::make('loc_exclusive_use')
                    ->label('Exclusive')
                    ->boolean(),
                IconColumn::make('loc_allows_ceremony_on_site')
                    ->label('Ceremony')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('loc_structure_type')
                    ->label('Structure type')
                    ->options(Supplier::LOCATION_STRUCTURE_TYPES),
                SelectFilter::make('loc_geo_area')
                    ->label('Geographic area')
                    ->options([
                        'Toscana' => 'Toscana',
                        'Lago di Como' => 'Lago di Como',
                        'Umbria' => 'Umbria',
                    ]),
                SelectFilter::make('loc_catering_type')
                    ->label('Catering type')
                    ->options(Supplier::LOCATION_CATERING_TYPES),
                Filter::make('guest_capacity')
                    ->label('Min guests')
                    ->form([
                        Components\TextInput::make('min_guests')
                            ->label('At least')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['min_guests'] ?? null),
                            fn (Builder $query): Builder => $query->where('loc_guest_max', '>=', (int) $data['min_guests'])
                        );
                    }),
                TernaryFilter::make('loc_has_rooms')
                    ->label('Rooms'),
                TernaryFilter::make('loc_exclusive_use')
                    ->label('Exclusive use'),
                TernaryFilter::make('loc_allows_external_catering')
                    ->label('External catering'),
                TernaryFilter::make('loc_allows_ceremony_on_site')
                    ->label('Ceremony on site'),
                TernaryFilter::make('loc_accessible')
                    ->label('Accessible'),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
