<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-key';

    protected static string | UnitEnum | null $navigationGroup = 'Security';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('roles'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group')
                    ->badge()
                    ->sortable(),
                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn (): array => Permission::query()
                        ->orderBy('group')
                        ->pluck('group', 'group')
                        ->all()),
            ])
            ->defaultSort('group')
            ->paginated([10, 25, 50]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
        ];
    }
}
