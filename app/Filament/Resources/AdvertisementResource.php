<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Enums\AdvertisementStatus;
use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use App\Support\Enums\EnumOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static string | UnitEnum | null $navigationGroup = 'Revenue';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Advertisement Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('media_type')
                        ->options([
                            'image' => 'Image',
                            'video' => 'Video',
                        ])
                        ->required(),
                    FileUpload::make('path')
                        ->label('Media file')
                        ->disk(config('community_will.media.ad_disk'))
                        ->directory('advertisements/media')
                        ->acceptedFileTypes(['image/*', 'video/*'])
                        ->required(),
                    FileUpload::make('thumbnail_path')
                        ->label('Thumbnail')
                        ->disk(config('community_will.media.ad_disk'))
                        ->directory('advertisements/thumbnails')
                        ->image(),
                    TextInput::make('destination_url')
                        ->url()
                        ->required()
                        ->columnSpanFull(),
                    Select::make('status')
                        ->options(EnumOptions::for(AdvertisementStatus::class))
                        ->required(),
                    DateTimePicker::make('starts_at'),
                    DateTimePicker::make('ends_at'),
                    TextInput::make('priority')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    TextInput::make('slot_interval')
                        ->numeric()
                        ->default((string) config('community_will.feed.ad_interval', 5))
                        ->required(),
                    Select::make('counties')
                        ->relationship('counties', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('counties'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('media_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('counties_count')
                    ->label('Counties')
                    ->sortable(),
                TextColumn::make('priority')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(AdvertisementStatus::class)),
                SelectFilter::make('media_type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
