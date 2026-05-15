<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Filament\Resources\PostArchiveResource\Pages;
use App\Models\PostArchive;
use Filament\Forms\Components\DatePicker;
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

class PostArchiveResource extends Resource
{
    protected static ?string $model = PostArchive::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Archive Bucket')
                ->schema([
                    Select::make('news_post_id')
                        ->relationship('post', 'title')
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Select::make('county_id')
                        ->relationship('county', 'name')
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('archive_date')
                        ->required(),
                    TextInput::make('price_cents')
                        ->numeric()
                        ->required(),
                    TextInput::make('currency')
                        ->required()
                        ->maxLength(3),
                    Select::make('access_scope')
                        ->options([
                            'day_pass' => 'Day Pass',
                            'bundle' => 'Bundle',
                        ])
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['post', 'county']))
            ->columns([
                TextColumn::make('county.name')
                    ->label('County')
                    ->sortable(),
                TextColumn::make('post.title')
                    ->label('Post')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('archive_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->state(fn (PostArchive $record): string => sprintf('%s %.2f', $record->currency, $record->price_cents / 100)),
                TextColumn::make('access_scope')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('county_id')
                    ->label('County')
                    ->relationship('county', 'name'),
                SelectFilter::make('access_scope')
                    ->options([
                        'day_pass' => 'Day Pass',
                        'bundle' => 'Bundle',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('archive_date', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostArchives::route('/'),
            'edit' => Pages\EditPostArchive::route('/{record}/edit'),
        ];
    }
}
