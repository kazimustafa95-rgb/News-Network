<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | UnitEnum | null $navigationGroup = 'Security';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('permissions')
                        ->relationship('permissions', 'name')
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['permissions', 'users']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
