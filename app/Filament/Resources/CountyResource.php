<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Enums\CountyStatus;
use App\Filament\Resources\CountyResource\Pages;
use App\Models\County;
use App\Support\Enums\EnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CountyResource extends Resource
{
    protected static ?string $model = County::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('County Details')
                ->schema([
                    Select::make('region_id')
                        ->relationship('region', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->maxLength(255),
                    Select::make('status')
                        ->options(EnumOptions::for(CountyStatus::class))
                        ->default(CountyStatus::Active->value)
                        ->required(),
                    DatePicker::make('launch_date')
                        ->default(now()->toDateString()),
                    TextInput::make('timezone')
                        ->default('America/Chicago')
                        ->required()
                        ->maxLength(64),
                    Toggle::make('is_featured')
                        ->label('Featured county')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('region.country'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region.name')
                    ->label('Region')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region.country.name')
                    ->label('Country')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('launch_date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(CountyStatus::class)),
                SelectFilter::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounties::route('/'),
            'create' => Pages\CreateCounty::route('/create'),
            'edit' => Pages\EditCounty::route('/{record}/edit'),
        ];
    }
}
