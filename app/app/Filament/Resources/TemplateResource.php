<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $pluralModelLabel = 'Templates';

    protected static ?string $modelLabel = 'Template';

    protected static string|\UnitEnum|null $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 82;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                            if (filled($get('slug'))) {
                                return;
                            }

                            $set('slug', Str::slug((string) $state));
                        }),
                    Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Use a stable slug such as proposal, contract or reminder-email.'),
                    Components\TextInput::make('language')
                        ->default('en')
                        ->required()
                        ->maxLength(10),
                    Components\Select::make('type')
                        ->required()
                        ->default(Template::TYPE_HTML)
                        ->options([
                            Template::TYPE_HTML => 'HTML',
                            Template::TYPE_TEXT_PLAIN => 'Text plain',
                        ])
                        ->native(false)
                        ->live(),
                    Components\RichEditor::make('content')
                        ->label('HTML content')
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => $get('type') === Template::TYPE_HTML)
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'h2',
                            'h3',
                            'bulletList',
                            'orderedList',
                            'blockquote',
                            'link',
                            'undo',
                            'redo',
                        ]),
                    Components\Textarea::make('content')
                        ->label('Plain text content')
                        ->rows(24)
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => $get('type') === Template::TYPE_TEXT_PLAIN)
                        ->helperText('Use this mode for simple plain text templates such as SMS or short email bodies.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('language')
                    ->badge(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Template::TYPE_TEXT_PLAIN ? 'Text plain' : 'HTML'),
                TextColumn::make('content')
                    ->label('Preview')
                    ->html()
                    ->limit(90)
                    ->tooltip(fn (Template $record): string => Str::of(strip_tags((string) $record->content))->limit(240)->value()),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
