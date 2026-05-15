<?php

namespace App\Filament\Resources;

use App\Enums\UserSubmissionStatus;
use App\Filament\Resources\UserSubmissionResource\Pages;
use App\Models\PostCategory;
use App\Models\PostSubcategory;
use App\Models\PostVideo;
use App\Models\UserSubmission;
use App\Services\Submission\SubmissionReviewService;
use App\Support\Enums\EnumOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class UserSubmissionResource extends Resource
{
    protected static ?string $model = UserSubmission::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user.profile', 'county', 'reviewer', 'videos'])->withCount('videos'))
            ->columns([
                TextColumn::make('primary_media_preview')
                    ->label('Media')
                    ->html()
                    ->getStateUsing(fn (UserSubmission $record): HtmlString => static::renderPrimaryMediaCell($record)),
                TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('county.name')
                    ->label('County')
                    ->sortable(),
                TextColumn::make('location_label')
                    ->label('Location')
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('videos_count')
                    ->label('Media')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->toggleable(),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(UserSubmissionStatus::class)),
                SelectFilter::make('county_id')
                    ->label('County')
                    ->relationship('county', 'name'),
            ])
            ->recordActions([
                Action::make('manage_media')
                    ->label('Media')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->schema(fn (UserSubmission $record): array => [
                        Section::make('Submission Media')
                            ->schema([
                                Placeholder::make('submission_media_preview')
                                    ->label('Current Media')
                                    ->content(static::renderSubmittedMediaPreview($record))
                                    ->columnSpanFull(),
                                FileUpload::make('submission_media_files')
                                    ->label('Update Media')
                                    ->disk(config('community_will.media.submission_disk'))
                                    ->directory('posts/originals')
                                    ->storeFiles(false)
                                    ->fetchFileInformation(false)
                                    ->visibility('public')
                                    ->multiple()
                                    ->reorderable()
                                    ->downloadable()
                                    ->openable()
                                    ->acceptedFileTypes(['image/*', 'video/*'])
                                    ->helperText('Add, remove, reorder, or replace the submission media files.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),
                    ])
                    ->fillForm(fn (UserSubmission $record): array => [
                        'submission_media_files' => static::existingMediaPaths($record),
                    ])
                    ->action(function (UserSubmission $record, array $data): void {
                        $normalizedMedia = static::normalizeUploadedMediaState((array) ($data['submission_media_files'] ?? []));

                        if ($normalizedMedia !== [] || ! $record->videos()->exists()) {
                            static::syncSubmissionMedia($record, $normalizedMedia);
                        }
                    }),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (UserSubmission $record): bool => $record->status === UserSubmissionStatus::Pending)
                    ->authorize('approve')
                    ->schema(fn (UserSubmission $record): array => [
                        Section::make('Original Submission')
                            ->schema([
                                TextInput::make('submitted_title')
                                    ->label('Submitted Title')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('submitted_location')
                                    ->label('Submitted Location')
                                    ->disabled()
                                    ->dehydrated(false),
                                Textarea::make('submitted_description')
                                    ->label('Submitted Description')
                                    ->rows(6)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                Placeholder::make('submitted_media_preview')
                                    ->label('Submitted Media')
                                    ->content(static::renderSubmittedMediaPreview($record))
                                    ->columnSpanFull(),
                                FileUpload::make('story_media_files')
                                    ->label('Update Story Media')
                                    ->disk(config('community_will.media.submission_disk'))
                                    ->directory('posts/originals')
                                    ->storeFiles(false)
                                    ->fetchFileInformation(false)
                                    ->visibility('public')
                                    ->multiple()
                                    ->reorderable()
                                    ->downloadable()
                                    ->openable()
                                    ->acceptedFileTypes(['image/*', 'video/*'])
                                    ->helperText('Adjust the submission media before publishing this story.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Section::make('Publish Story')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->maxLength(255),
                                Textarea::make('excerpt')
                                    ->label('Short Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Textarea::make('body')
                                    ->label('Description / Story Body')
                                    ->rows(10)
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('post_category_id')
                                    ->label('Category')
                                    ->options(fn (): array => PostCategory::query()
                                        ->orderBy('sort_order')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
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
                                Textarea::make('review_notes')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                \Filament\Forms\Components\Toggle::make('publish_now')
                                    ->label('Publish immediately')
                                    ->default(true),
                                \Filament\Forms\Components\Toggle::make('is_featured')
                                    ->label('Feature this story'),
                                \Filament\Forms\Components\Toggle::make('is_breaking')
                                    ->label('Mark as breaking'),
                            ])
                            ->columns(2),
                    ])
                    ->fillForm(fn (UserSubmission $record): array => [
                        'post_category_id' => PostCategory::query()->where('slug', 'community')->value('id'),
                        'post_subcategory_id' => PostSubcategory::query()->where('slug', 'local-updates')->value('id'),
                        'submitted_title' => $record->title,
                        'submitted_location' => $record->location_label,
                        'submitted_description' => $record->description,
                        'story_media_files' => static::existingMediaPaths($record),
                        'title' => $record->title ?: Str::limit($record->description, 70, ''),
                        'body' => $record->description,
                        'publish_now' => true,
                    ])
                    ->action(function (UserSubmission $record, array $data): void {
                        $normalizedMedia = static::normalizeUploadedMediaState((array) ($data['story_media_files'] ?? []));

                        if ($normalizedMedia !== [] || ! $record->videos()->exists()) {
                            static::syncSubmissionMedia($record, $normalizedMedia);
                        }
                        unset($data['story_media_files']);

                        $approvedSubmission = app(SubmissionReviewService::class)->approve($record, auth()->user(), $data);

                        $record->setRawAttributes($approvedSubmission->getAttributes(), true);

                        foreach ($approvedSubmission->getRelations() as $relation => $related) {
                            $record->setRelation($relation, $related);
                        }
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (UserSubmission $record): bool => $record->status === UserSubmissionStatus::Pending)
                    ->authorize('reject')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Reason')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (UserSubmission $record, array $data): void {
                        app(SubmissionReviewService::class)->reject($record, auth()->user(), (string) $data['review_notes']);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('15s');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) UserSubmission::query()
            ->where('status', UserSubmissionStatus::Pending->value)
            ->count();
    }

    public static function existingMediaPaths(UserSubmission $submission): array
    {
        return $submission->videos()
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    public static function normalizeUploadedMediaState(array $files): array
    {
        $disk = config('community_will.media.submission_disk');
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
                        // Livewire may already have moved the temp upload.
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

    public static function syncSubmissionMedia(UserSubmission $submission, array $paths): void
    {
        $disk = config('community_will.media.submission_disk');
        $paths = array_values(array_unique(array_filter($paths)));
        $existingMedia = $submission->videos()->get()->keyBy('path');

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
                'news_post_id' => $existing?->news_post_id,
                'user_submission_id' => $submission->id,
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

            $submission->videos()->create($payload);
        }

        $removedMedia = $submission->videos()
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

    public static function resolvePrimaryMediaPreview(UserSubmission $submission): ?string
    {
        $media = $submission->videos
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

    public static function renderPrimaryMediaCell(UserSubmission $submission): HtmlString
    {
        $media = $submission->videos
            ->sortByDesc('is_primary')
            ->first();

        if (! $media) {
            return new HtmlString('<div style="font-size: 12px; color: #9ca3af;">No media</div>');
        }

        $previewPath = static::resolvePrimaryMediaPreview($submission);
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

    public static function renderSubmittedMediaPreview(UserSubmission $submission): HtmlString
    {
        $submission->loadMissing('videos');

        if ($submission->videos->isEmpty()) {
            return new HtmlString('<div class="text-sm text-gray-500">No media was attached to this submission.</div>');
        }

        $cards = $submission->videos
            ->map(function (PostVideo $media): string {
                $url = $media->resolveMediaUrl($media->path);
                $mime = e($media->mime_type ?: 'media file');
                $status = e($media->processing_status ?: 'uploaded');

                if (str_starts_with((string) $media->mime_type, 'image/') && $url) {
                    return <<<HTML
<div style="border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px; background: #ffffff;">
    <a href="{$url}" target="_blank" style="text-decoration: none;">
        <img src="{$url}" alt="Submitted media" style="width: 100%; height: 180px; object-fit: cover; border-radius: 10px;" />
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserSubmissions::route('/'),
        ];
    }
}
