<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Enums\EnumOptions;
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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | UnitEnum | null $navigationGroup = 'Audience';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),
            Section::make('Access')
                ->schema([
                    Select::make('status')
                        ->options(EnumOptions::for(UserStatus::class))
                        ->required(),
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['roles', 'profile', 'subscriptions']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles_summary')
                    ->label('Roles')
                    ->state(fn (User $record): string => $record->roles->pluck('name')->join(', '))
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subscription_status')
                    ->label('Subscription')
                    ->badge()
                    ->state(fn (User $record): string => $record->activeSubscription()?->status?->value ?? 'none'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(UserStatus::class)),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
