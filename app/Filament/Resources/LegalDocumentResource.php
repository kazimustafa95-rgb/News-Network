<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalDocumentResource\Pages;
use App\Models\LegalDocument;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class LegalDocumentResource extends Resource
{
    protected static ?string $model = LegalDocument::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 15;

    public static function getNavigationLabel(): string
    {
        return 'Legal Pages';
    }

    public static function getModelLabel(): string
    {
        return 'Legal Page';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Legal Pages';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Document Details')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->disabled()
                        ->dehydrated(false),
                    Placeholder::make('public_url')
                        ->label('Public URL')
                        ->content(fn (?LegalDocument $record): HtmlString => new HtmlString(
                            $record?->getPublicUrl()
                                ? '<a href="'.e($record->getPublicUrl()).'" target="_blank" style="color: #2563eb; text-decoration: underline;">'.e($record->getPublicUrl()).'</a>'
                                : '<span style="color: #6b7280;">Public URL will be available after the record is created.</span>'
                        ))
                        ->columnSpanFull(),
                    Textarea::make('summary')
                        ->rows(3)
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->label('Document Content')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'h2',
                            'h3',
                            'blockquote',
                            'bulletList',
                            'orderedList',
                            'link',
                            'undo',
                            'redo',
                        ])
                        ->columnSpanFull(),
                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('public_url')
                    ->label('Public URL')
                    ->state(fn (LegalDocument $record): string => $record->getPublicUrl() ?? '-')
                    ->copyable()
                    ->wrap(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('title')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalDocuments::route('/'),
            'edit' => Pages\EditLegalDocument::route('/{record}/edit'),
        ];
    }
}
