<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('event')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Actor')
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->toggleable(),
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
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
