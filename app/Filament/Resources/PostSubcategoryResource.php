<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostSubcategoryResource\Pages;
use App\Models\PostSubcategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PostSubcategoryResource extends Resource
{
    protected static ?string $model = PostSubcategory::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subcategory Details')
                ->schema([
                    Select::make('post_category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('post_category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
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
            ->defaultSort('sort_order')
            ->paginated([10, 25, 50]);
    }

    public static function normalizePayload(array $data): array
    {
        if (blank($data['slug'] ?? null) && filled($data['name'] ?? null)) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostSubcategories::route('/'),
            'create' => Pages\CreatePostSubcategory::route('/create'),
            'edit' => Pages\EditPostSubcategory::route('/{record}/edit'),
        ];
    }
}
