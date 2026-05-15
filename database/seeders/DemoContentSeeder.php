<?php

namespace Database\Seeders;

use App\Enums\AdvertisementStatus;
use App\Enums\ArchivePurchaseStatus;
use App\Enums\NewsPostStatus;
use App\Models\Advertisement;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\PostCategory;
use App\Models\PostSubcategory;
use App\Models\User;
use App\Models\UserSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::query()->where('slug', 'butler-county')->firstOrFail();
        $admin = User::query()->where('email', 'admin@communitywill.test')->firstOrFail();
        $subscriber = User::query()->where('email', 'subscriber@communitywill.test')->firstOrFail();
        $generalCategoryId = PostCategory::query()->where('slug', 'general')->value('id');
        $communityCategoryId = PostCategory::query()->where('slug', 'community')->value('id');
        $generalSubcategoryId = PostSubcategory::query()->where('slug', 'top-stories')->value('id');
        $communitySubcategoryId = PostSubcategory::query()->where('slug', 'local-updates')->value('id');

        foreach (range(1, 6) as $index) {
            $isCommunity = $index % 2 === 0;
            $post = NewsPost::withTrashed()->firstOrNew(['slug' => 'butler-news-'.$index]);

            if ($post->trashed()) {
                $post->restore();
            }

            $post->fill(
                [
                    'county_id' => $county->id,
                    'author_id' => $admin->id,
                    'title' => 'Butler County community update '.$index,
                    'excerpt' => 'Sample excerpt for Butler County community update '.$index.'.',
                    'body' => 'This is a seeded article body for Butler County community update '.$index.'.',
                    'post_category_id' => $isCommunity ? $communityCategoryId : $generalCategoryId,
                    'post_subcategory_id' => $isCommunity ? $communitySubcategoryId : $generalSubcategoryId,
                    'topic' => $isCommunity ? 'community' : 'general',
                    'source_type' => 'admin_original',
                    'status' => NewsPostStatus::Published->value,
                    'is_featured' => $index <= 2,
                    'is_breaking' => $index <= 3,
                    'published_at' => now()->subDays($index - 1),
                ],
            );
            $post->save();

            $post->videos()->updateOrCreate(
                ['path' => 'posts/videos/demo-'.$index.'.mp4'],
                [
                    'disk' => 'public',
                    'thumbnail_path' => 'posts/thumbnails/demo-'.$index.'.jpg',
                    'mime_type' => 'video/mp4',
                    'file_size' => 1024 * 1024 * 20,
                    'is_primary' => true,
                    'processing_status' => 'ready',
                    'processed_at' => now(),
                ],
            );
        }

        $archivedPost = NewsPost::withTrashed()->firstOrNew(['slug' => 'butler-archived-news']);

        if ($archivedPost->trashed()) {
            $archivedPost->restore();
        }

        $archivedPost->fill(
            [
                'county_id' => $county->id,
                'author_id' => $admin->id,
                'title' => 'Archived Butler County update',
                'excerpt' => 'Older Butler County story now behind archive access.',
                'body' => 'This seeded post simulates an archived item behind the older news paywall.',
                'post_category_id' => $generalCategoryId,
                'post_subcategory_id' => $generalSubcategoryId,
                'topic' => 'general',
                'source_type' => 'admin_original',
                'status' => NewsPostStatus::Archived->value,
                'published_at' => now()->subDays(10),
                'archive_at' => now()->subDays(3),
                'archived_at' => now()->subDays(2),
            ],
        );
        $archivedPost->save();

        $archivedPost->archive()->updateOrCreate([], [
            'county_id' => $county->id,
            'archive_date' => now()->subDays(10)->toDateString(),
            'price_cents' => 999,
            'currency' => 'USD',
            'access_scope' => 'day_pass',
        ]);

        $subscriber->archivePurchases()->updateOrCreate(
            ['provider_transaction_id' => 'arch_demo_txn_001'],
            [
                'county_id' => $county->id,
                'archive_date' => now()->subDays(10)->toDateString(),
                'provider' => 'manual',
                'amount_cents' => 999,
                'currency' => 'USD',
                'status' => ArchivePurchaseStatus::Paid->value,
                'purchased_at' => now()->subDay(),
                'verified_at' => now()->subDay(),
            ],
        );

        $submission = UserSubmission::query()->updateOrCreate(
            ['user_id' => $subscriber->id, 'description' => 'Community members gathered for a local school fundraiser.'],
            [
                'county_id' => $county->id,
                'title' => 'Local school fundraiser draws strong turnout',
                'location_label' => 'Greenville, Butler County',
                'status' => 'pending',
            ],
        );

        $submission->videos()->updateOrCreate(
            ['path' => 'posts/originals/demo-submission.mp4'],
            [
                'disk' => 'public',
                'thumbnail_path' => 'submissions/thumbnails/demo-submission.jpg',
                'mime_type' => 'video/mp4',
                'file_size' => 1024 * 1024 * 12,
                'is_primary' => true,
                'processing_status' => 'pending',
            ],
        );

        $advertisement = Advertisement::query()->updateOrCreate(
            ['name' => 'Butler County Sponsor'],
            [
                'media_type' => 'image',
                'disk' => 'public',
                'path' => 'ads/media/butler-county-sponsor.jpg',
                'thumbnail_path' => 'ads/media/butler-county-sponsor-thumb.jpg',
                'destination_url' => 'https://example.com/butler-county-sponsor',
                'status' => AdvertisementStatus::Active->value,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'priority' => 10,
                'slot_interval' => 5,
                'meta' => ['campaign' => Str::uuid()->toString()],
            ],
        );

        $advertisement->counties()->syncWithoutDetaching([$county->id]);
    }
}
