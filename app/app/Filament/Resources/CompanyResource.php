<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Companies';

    protected static ?string $pluralModelLabel = 'Companies';
    protected static ?string $modelLabel = 'Company';
    protected static string|\UnitEnum|null $navigationGroup = 'Setup';
    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Anagrafica')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('company')->required()->maxLength(255),
                    Components\TextInput::make('name')->required()->maxLength(255),
                    Components\TextInput::make('company_name')->required()->maxLength(255),
                    Components\TextInput::make('status')->maxLength(50),
                    Components\TextInput::make('onboard')->maxLength(50),
                    Components\Toggle::make('integrated'),
                    Components\TextInput::make('category_type')->maxLength(100),
                    Components\TextInput::make('reliability_type')->maxLength(50),
                    Components\TextInput::make('company_type')->maxLength(50),
                    Components\TextInput::make('group')->maxLength(100),
                ]),

            Section::make('Fiscale')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('vat')->maxLength(50),
                    Components\TextInput::make('tax_code')->maxLength(50),
                    Components\TextInput::make('sdi')->maxLength(50),
                    Components\TextInput::make('pec')->email()->maxLength(255),
                    Components\TextInput::make('iban')->maxLength(64),
                ]),

            Section::make('Indirizzo')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('address_1')->maxLength(255),
                    Components\TextInput::make('address_2')->maxLength(255),
                    Components\TextInput::make('zipcode')->maxLength(20),
                    Components\TextInput::make('city')->required()->maxLength(100),
                    Components\TextInput::make('province')->maxLength(10),
                ]),

            Section::make('Codici')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('client_code')->maxLength(50),
                    Components\TextInput::make('payment_code')->maxLength(50),
                    Components\TextInput::make('api_key')->maxLength(255),
                    Components\TextInput::make('comipa_code')->maxLength(100),
                    Components\TextInput::make('old_comipa_code')->maxLength(100),
                    Components\TextInput::make('commercial_agent_code')->maxLength(100),
                    Components\TextInput::make('max_office')->maxLength(50),
                ]),

            Section::make('Contatti e Web')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('url')->maxLength(255),
                    static::jsonTextarea('contacts'),
                    static::jsonTextarea('logo'),
                    static::jsonTextarea('webhook_data'),
                ]),

            Section::make('Pagamenti e Config')
                ->columns(2)
                ->schema([
                    Components\TagsInput::make('payments'),
                    static::jsonTextarea('mandatory_payment'),
                    static::jsonTextarea('mandatory_payment_who'),
                    static::jsonTextarea('mandatory_payment_offline'),
                    static::jsonTextarea('mandatory_payment_offline_who'),
                    static::jsonTextarea('mandatory_payment_coupon'),
                    static::jsonTextarea('mandatory_payment_coupon_who'),
                    static::jsonTextarea('preavviso'),
                    static::jsonTextarea('base_commissions'),
                    static::jsonTextarea('base_commissions_comipa'),
                    static::jsonTextarea('time_confirmation'),
                ]),

            Section::make('Flag e Metadati')
                ->columns(3)
                ->schema([
                    Components\Toggle::make('googleads'),
                    Components\Toggle::make('googlereservewith'),
                    Components\Toggle::make('company_without_stamp_duty'),
                    Components\Toggle::make('skip_realtime_check'),
                    Components\TextInput::make('blood_sampling_price')->numeric(),
                    Components\TextInput::make('video_old')->maxLength(10),
                    Components\TagsInput::make('visibility'),
                    Components\TextInput::make('visibility_old'),
                    Components\TagsInput::make('seed'),
                    Components\DatePicker::make('prima_prenotazione'),
                    Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                    Components\Textarea::make('invoices_notes_xml')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               /* TextColumn::make('_id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(),*/
                //TextColumn::make('company')->searchable()->sortable(),
                //taglia name a 30 caratteri per evitare problemi di visualizzazione in tabella, ma lascia la possibilità di vedere il nome completo nella vista dettagli
                TextColumn::make('name')->searchable()->sortable()->formatStateUsing(fn ($state) => strlen($state) > 30 ? substr($state, 0, 30) . '...' : $state)
                    ->tooltip(fn ($state) => $state),
                //TextColumn::make('company_name')->searchable()->toggleable(),
                //TextColumn::make('vat')->searchable()->toggleable(),
                //TextColumn::make('tax_code')->searchable()->toggleable(),
                //TextColumn::make('client_code')->searchable()->toggleable(),
                //TextColumn::make('payment_code')->searchable()->toggleable(),
                TextColumn::make('city')->searchable()->sortable(),
                //TextColumn::make('province')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                //TextColumn::make('category_type')->toggleable(),
                //TextColumn::make('reliability_type')->toggleable(),
                TextColumn::make('payments')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('googleads')->boolean()->toggleable(),
                IconColumn::make('integrated')->boolean()->toggleable(),
                //TextColumn::make('onboard')->toggleable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    private static function jsonTextarea(string $name): Components\Textarea
    {
        return Components\Textarea::make($name)
            ->rows(4)
            ->formatStateUsing(function ($state): ?string {
                if ($state === null || $state === '') {
                    return null;
                }

                if (is_string($state)) {
                    return $state;
                }

                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
            ->dehydrateStateUsing(function ($state) {
                if ($state === null || trim((string) $state) === '') {
                    return null;
                }

                $decoded = json_decode((string) $state, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
            });
    }
}
