<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Support\Enums\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
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

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static string | UnitEnum | null $navigationGroup = 'Revenue';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subscription')
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider_product_id')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider_transaction_id')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    TextInput::make('plan_code')
                        ->required(),
                    Select::make('status')
                        ->options(EnumOptions::for(SubscriptionStatus::class))
                        ->required(),
                    DateTimePicker::make('started_at'),
                    DateTimePicker::make('ends_at'),
                    DateTimePicker::make('cancelled_at'),
                    DateTimePicker::make('verified_at'),
                    Toggle::make('auto_renew')
                        ->label('Auto renew'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('plan_code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('auto_renew')
                    ->boolean()
                    ->label('Auto'),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('verified_at')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(SubscriptionStatus::class)),
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
