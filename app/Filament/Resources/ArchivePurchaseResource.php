<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Enums\ArchivePurchaseStatus;
use App\Filament\Resources\ArchivePurchaseResource\Pages;
use App\Models\ArchivePurchase;
use App\Support\Enums\EnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
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

class ArchivePurchaseResource extends Resource
{
    protected static ?string $model = ArchivePurchase::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static string | UnitEnum | null $navigationGroup = 'Revenue';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Archive Purchase')
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('county_id')
                        ->relationship('county', 'name')
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false),
                    DatePicker::make('archive_date')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider_transaction_id')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    TextInput::make('amount_cents')
                        ->numeric()
                        ->required(),
                    TextInput::make('currency')
                        ->required()
                        ->maxLength(3),
                    Select::make('status')
                        ->options(EnumOptions::for(ArchivePurchaseStatus::class))
                        ->required(),
                    DateTimePicker::make('purchased_at'),
                    DateTimePicker::make('verified_at'),
                    DateTimePicker::make('refunded_at'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'county']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('county.name')
                    ->label('County')
                    ->sortable(),
                TextColumn::make('archive_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->state(fn (ArchivePurchase $record): string => sprintf('%s %.2f', $record->currency, $record->amount_cents / 100)),
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('purchased_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(ArchivePurchaseStatus::class)),
                SelectFilter::make('county_id')
                    ->label('County')
                    ->relationship('county', 'name'),
                SelectFilter::make('provider')
                    ->options([
                        'apple' => 'Apple',
                        'google' => 'Google',
                        'stripe' => 'Stripe',
                        'manual' => 'Manual',
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
            'index' => Pages\ListArchivePurchases::route('/'),
            'edit' => Pages\EditArchivePurchase::route('/{record}/edit'),
        ];
    }
}
