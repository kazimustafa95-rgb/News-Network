<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminActionResource\Pages;
use App\Models\AdminAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AdminActionResource extends Resource
{
    protected static ?string $model = AdminAction::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | UnitEnum | null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Admin Actions';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('admin'))
            ->columns([
                TextColumn::make('action')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_type')
                    ->label('Target Type')
                    ->toggleable(),
                TextColumn::make('target_id')
                    ->label('Target ID')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListAdminActions::route('/'),
        ];
    }
}
