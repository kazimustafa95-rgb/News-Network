<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Enums\NewsPostStatus;
use App\Filament\Resources\NewsPostResource\Pages;
use App\Models\NewsPost;
use App\Models\PostSubcategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;
use App\Support\Enums\EnumOptions;

class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Post Details')
                ->schema([
                    Select::make('county_id')
                        ->relationship('county', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('author_id')
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->label('Short Description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('body')
                        ->label('Description / Story Body')
                        ->rows(12)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Publishing')
                ->schema([
                    Select::make('post_category_id')
                        ->label('Category')
                        ->relationship('postCategory', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('post_subcategory_id', null);
                        })
                        ->required(),
                    Select::make('post_subcategory_id')
                        ->label('Subcategory')
                        ->searchable()
                        ->preload()
                        ->options(fn (Get $get): array => PostSubcategory::query()
                            ->when(
                                $get('post_category_id'),
                                fn (Builder $query, $categoryId) => $query->where('post_category_id', $categoryId),
                                fn (Builder $query) => $query->whereRaw('1 = 0'),
                            )
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->disabled(fn (Get $get): bool => blank($get('post_category_id'))),
                    Select::make('status')
                        ->options(EnumOptions::for(NewsPostStatus::class))
                        ->required(),
                    DateTimePicker::make('published_at'),
                    DateTimePicker::make('archive_at'),
                    Toggle::make('is_featured')
                        ->label('Featured'),
                    Toggle::make('is_breaking')
                        ->label('Breaking news'),
                ])
                ->columns(2),
            Section::make('Media')
                ->schema([
                    Placeholder::make('current_media_preview')
                        ->label('Current Media')
                        ->content(fn (?NewsPost $record): HtmlString => static::renderStoredMediaPreview($record))
                        ->columnSpanFull(),
                    FileUpload::make('media_files')
                        ->label('Post Media')
                        ->disk(config('community_will.media.post_disk'))
                        ->directory('posts/originals')
                        ->storeFiles(false)
                        ->fetchFileInformation(false)
                        ->visibility('public')
                        ->multiple()
                        ->reorderable()
                        ->downloadable()
                        ->openable()
                        ->acceptedFileTypes(['image/*', 'video/*'])
                        ->helperText('Current media is shown above. Upload new files to replace the attached media for this story. The first file is used as the primary media.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['county', 'author', 'videos', 'postCategory', 'postSubcategory'])->withCount('videos'))
            ->columns([
                TextColumn::make('primary_media_preview')
                    ->label('Media')
                    ->html()
                    ->getStateUsing(fn (NewsPost $record): HtmlString => static::renderPrimaryMediaCell($record)),
                TextColumn::make('county.name')
                    ->label('County')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('postCategory.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('postSubcategory.name')
                    ->label('Subcategory')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('excerpt')
                    ->label('Description')
                    ->limit(80)
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                IconColumn::make('is_breaking')
                    ->boolean()
                    ->label('Breaking'),
                TextColumn::make('videos_count')
                    ->label('Media Files')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('county_id')
                    ->label('County')
                    ->relationship('county', 'name'),
                SelectFilter::make('post_category_id')
                    ->label('Category')
                    ->relationship('postCategory', 'name'),
                SelectFilter::make('post_subcategory_id')
                    ->label('Subcategory')
                    ->relationship('postSubcategory', 'name'),
                SelectFilter::make('status')
                    ->options(EnumOptions::for(NewsPostStatus::class)),
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function normalizePayload(array $data, ?NewsPost $record = null): array
    {
        $title = trim((string) ($data['title'] ?? ''));

        $slugCandidate = trim((string) ($data['slug'] ?? ''));
        $data['slug'] = NewsPost::ensureUniqueSlug(
            $slugCandidate !== '' ? $slugCandidate : $title,
            $record?->id,
        );

        if (filled($data['post_subcategory_id'] ?? null) && blank($data['post_category_id'] ?? null)) {
            $data['post_category_id'] = PostSubcategory::query()
                ->whereKey($data['post_subcategory_id'])
                ->value('post_category_id');
        }

        $data['topic'] = NewsPost::resolveLegacyTopicFromTaxonomyIds(
            isset($data['post_category_id']) ? (int) $data['post_category_id'] : null,
            isset($data['post_subcategory_id']) ? (int) $data['post_subcategory_id'] : null,
        );

        if (($data['status'] ?? null) === NewsPostStatus::Published->value && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        if (filled($data['published_at'] ?? null) && blank($data['archive_at'] ?? null)) {
            $data['archive_at'] = Carbon::parse($data['published_at'])
                ->copy()
                ->addDays(config('community_will.archive.days_visible', 7));
        }

        if (($data['status'] ?? null) === NewsPostStatus::Archived->value && blank($data['archived_at'] ?? null)) {
            $data['archived_at'] = now();
        }

        return $data;
    }

    public static function existingMediaPaths(NewsPost $post): array
    {
        $targetDisk = config('community_will.media.post_disk');

        return $post->videos()
            ->where('disk', $targetDisk)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    public static function normalizeUploadedMediaState(array $files): array
    {
        $disk = config('community_will.media.post_disk');
        $directory = 'posts/originals';

        return collect($files)
            ->map(function (mixed $file) use ($directory, $disk): ?string {
                if ($file instanceof TemporaryUploadedFile) {
                    $extension = $file->getClientOriginalExtension();
                    $storedPath = $file->storeAs(
                        $directory,
                        Str::ulid().($extension ? '.'.$extension : ''),
                        $disk,
                    );

                    try {
                        $file->delete();
                    } catch (\Throwable) {
                        // Livewire cleans temp uploads automatically if they already moved.
                    }

                    return $storedPath ?: null;
                }

                if (is_string($file)) {
                    $path = trim($file);

                    if ($path === '' || str_contains($path, 'livewire-tmp/')) {
                        return null;
                    }

                    return $path;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function syncUploadedMedia(NewsPost $post, array $paths): void
    {
        $disk = config('community_will.media.post_disk');
        $paths = array_values(array_unique(array_filter($paths)));
        $existingMedia = $post->videos()->get()->keyBy('path');

        foreach ($paths as $index => $path) {
            $existing = $existingMedia->get($path);
            $mimeType = $existing?->mime_type;
            $fileSize = $existing?->file_size;

            if ((! $mimeType || ! $fileSize) && $path !== '' && Storage::disk($disk)->exists($path)) {
                $mimeType ??= Storage::disk($disk)->mimeType($path) ?: null;
                $fileSize ??= Storage::disk($disk)->size($path) ?: null;
            }

            $isImage = str_starts_with((string) $mimeType, 'image/');
            $payload = [
                'disk' => $existing?->disk ?: $disk,
                'path' => $path,
                'thumbnail_path' => $isImage ? $path : $existing?->thumbnail_path,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'is_primary' => $index === 0,
                'processing_status' => $isImage ? 'ready' : ($existing?->processing_status ?: 'pending'),
                'processed_at' => $isImage ? now() : $existing?->processed_at,
            ];

            if ($existing) {
                $existing->fill($payload)->save();

                continue;
            }

            $post->videos()->create($payload);
        }

        $removedMedia = $post->videos()
            ->when(
                $paths !== [],
                fn (Builder $query) => $query->whereNotIn('path', $paths),
            )
            ->when(
                $paths === [],
                fn (Builder $query) => $query,
            )
            ->get();

        foreach ($removedMedia as $media) {
            if (in_array($media->path, $paths, true)) {
                continue;
            }

            if ($media->user_submission_id) {
                $media->update([
                    'news_post_id' => null,
                    'is_primary' => false,
                ]);

                continue;
            }

            if ($media->path && Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }

            if ($media->thumbnail_path
                && $media->thumbnail_path !== $media->path
                && Storage::disk($media->disk)->exists($media->thumbnail_path)) {
                Storage::disk($media->disk)->delete($media->thumbnail_path);
            }

            $media->delete();
        }
    }

    public static function renderPrimaryMediaCell(NewsPost $post): HtmlString
    {
        $media = $post->videos
            ->sortByDesc('is_primary')
            ->first();

        if (! $media) {
            return new HtmlString('<div style="font-size: 12px; color: #9ca3af;">No media</div>');
        }

        $previewPath = static::resolvePrimaryMediaPreview($post);
        $isVideo = str_starts_with((string) $media->mime_type, 'video/');
        $badgeLabel = $isVideo ? 'Video' : 'Image';
        $badgeBackground = $isVideo ? '#dbeafe' : '#dcfce7';
        $badgeColor = $isVideo ? '#1d4ed8' : '#166534';

        if ($previewPath) {
            $url = $media->resolveMediaUrl($previewPath);

            if ($url) {
                return new HtmlString(
                    '<div style="display: inline-flex; align-items: center; gap: 10px;">'
                    .'<img src="'.e($url).'" alt="'.$badgeLabel.' preview" style="width: 56px; height: 56px; object-fit: cover; border-radius: 14px; border: 1px solid #e5e7eb;" />'
                    .'<span style="display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 600; background: '.$badgeBackground.'; color: '.$badgeColor.';">'.$badgeLabel.'</span>'
                    .'</div>'
                );
            }
        }

        return new HtmlString(
            '<div style="display: inline-flex; align-items: center; justify-content: center; min-width: 56px; height: 56px; border-radius: 14px; padding: 0 12px; background: '.$badgeBackground.'; color: '.$badgeColor.'; font-size: 11px; font-weight: 700; border: 1px solid #bfdbfe;">'
            .$badgeLabel.
            '</div>'
        );
    }

    public static function renderStoredMediaPreview(?NewsPost $post): HtmlString
    {
        if (! $post?->exists) {
            return new HtmlString('<div class="text-sm text-gray-500">Upload images or videos after creating the post.</div>');
        }

        $post->loadMissing('videos');

        if ($post->videos->isEmpty()) {
            return new HtmlString('<div class="text-sm text-gray-500">No media is attached to this post yet.</div>');
        }

        $cards = $post->videos
            ->sortByDesc('is_primary')
            ->map(function ($media): string {
                $url = $media->resolveMediaUrl($media->path);

                $mime = e($media->mime_type ?: 'media file');
                $status = e($media->processing_status ?: 'uploaded');

                if (str_starts_with((string) $media->mime_type, 'image/') && $url) {
                    return <<<HTML
<div style="border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px; background: #ffffff;">
    <a href="{$url}" target="_blank" style="text-decoration: none;">
        <img src="{$url}" alt="Post media" style="width: 100%; height: 180px; object-fit: cover; border-radius: 10px;" />
    </a>
    <div style="margin-top: 8px; font-size: 12px; color: #4b5563;">{$mime} &bull; {$status}</div>
</div>
HTML;
                }

                $posterUrl = $media->resolveMediaUrl($media->thumbnail_path);
                $posterAttribute = $posterUrl ? ' poster="'.e($posterUrl).'"' : '';
                $openLink = $url
                    ? '<a href="'.e($url).'" target="_blank" style="display: inline-block; padding: 8px 14px; border-radius: 999px; background: #2563eb; color: #ffffff; text-decoration: none; font-size: 13px;">Open Media</a>'
                    : '<span style="display: inline-block; padding: 8px 14px; border-radius: 999px; background: #e5e7eb; color: #6b7280; font-size: 13px;">Media unavailable</span>';
                $sourceTag = $url ? '<source src="'.e($url).'" type="'.$mime.'" />' : '';

                return <<<HTML
<div style="border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; background: #ffffff;">
    <div style="font-weight: 600; color: #111827; margin-bottom: 6px;">Video Attachment</div>
    <video controls preload="metadata"{$posterAttribute} style="width: 100%; height: 220px; object-fit: cover; border-radius: 10px; background: #111827; margin-bottom: 12px;">
        {$sourceTag}
        Your browser does not support the video tag.
    </video>
    <div style="font-size: 12px; color: #4b5563; margin-bottom: 12px;">{$mime} &bull; {$status}</div>
    {$openLink}
</div>
HTML;
            })
            ->implode('');

        return new HtmlString(
            '<div style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">'.$cards.'</div>'
        );
    }

    public static function resolvePrimaryMediaPreview(NewsPost $post): ?string
    {
        $media = $post->videos
            ->sortByDesc('is_primary')
            ->first();

        if (! $media) {
            return null;
        }

        if ($media->thumbnail_path) {
            return $media->thumbnail_path;
        }

        return str_starts_with((string) $media->mime_type, 'image/')
            ? $media->path
            : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsPosts::route('/'),
            'create' => Pages\CreateNewsPost::route('/create'),
            'edit' => Pages\EditNewsPost::route('/{record}/edit'),
        ];
    }
}
