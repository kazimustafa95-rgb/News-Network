<?php

namespace Tests\Feature\Admin;

use App\Enums\UserSubmissionStatus;
use App\Models\County;
use App\Models\PostCategory;
use App\Models\PostSubcategory;
use App\Models\PostVideo;
use App\Models\User;
use App\Models\UserSubmission;
use App\Services\Submission\SubmissionReviewService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionApprovalMediaPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            GeographySeeder::class,
            AdminUserSeeder::class,
        ]);
    }

    public function test_submission_media_is_promoted_to_post_media_disk_on_approval(): void
    {
        config([
            'community_will.media.submission_disk' => 'public',
            'community_will.media.post_disk' => 'public',
        ]);

        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();
        $county = County::query()->firstOrFail();
        $category = PostCategory::query()->where('slug', 'community')->firstOrFail();
        $subcategory = PostSubcategory::query()->where('slug', 'local-updates')->firstOrFail();

        $sourcePath = 'posts/originals/test-approval-video.mp4';
        Storage::disk('public')->put($sourcePath, 'submission video content');

        $submission = UserSubmission::query()->create([
            'user_id' => $subscriber->id,
            'county_id' => $county->id,
            'title' => 'School board update',
            'location_label' => 'Greenville, Butler County',
            'description' => 'Community members submitted a school board update.',
            'status' => UserSubmissionStatus::Pending->value,
        ]);

        $media = $submission->videos()->create([
            'disk' => 'public',
            'path' => $sourcePath,
            'mime_type' => 'video/mp4',
            'file_size' => Storage::disk('public')->size($sourcePath),
            'is_primary' => true,
            'processing_status' => 'ready',
            'processed_at' => now(),
        ]);

        app(SubmissionReviewService::class)->approve($submission, $admin, [
            'title' => 'School board update',
            'body' => 'Community members submitted a school board update.',
            'post_category_id' => $category->id,
            'post_subcategory_id' => $subcategory->id,
            'publish_now' => true,
        ]);

        $media->refresh();

        $this->assertNotNull($media->news_post_id);
        $this->assertSame('public', $media->disk);
        $this->assertSame($sourcePath, $media->path);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
    }
}
