<?php

namespace Tests\Feature\Admin;

use App\Enums\UserSubmissionStatus;
use App\Models\County;
use App\Models\PostCategory;
use App\Models\PostSubcategory;
use App\Models\User;
use App\Models\UserSubmission;
use App\Services\Submission\SubmissionReviewService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionReviewNotificationTest extends TestCase
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

    public function test_submitter_receives_approval_notification_after_review(): void
    {
        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();
        $county = County::query()->firstOrFail();
        $category = PostCategory::query()->where('slug', 'community')->firstOrFail();
        $subcategory = PostSubcategory::query()->where('slug', 'local-updates')->firstOrFail();

        $submission = UserSubmission::query()->create([
            'user_id' => $subscriber->id,
            'county_id' => $county->id,
            'title' => 'Neighborhood clean-up success',
            'location_label' => 'Greenville',
            'description' => 'Residents gathered for a weekend clean-up event.',
            'status' => UserSubmissionStatus::Pending->value,
        ]);

        app(SubmissionReviewService::class)->approve($submission, $admin, [
            'title' => 'Neighborhood clean-up success',
            'body' => 'Residents gathered for a weekend clean-up event.',
            'post_category_id' => $category->id,
            'post_subcategory_id' => $subcategory->id,
            'publish_now' => true,
            'review_notes' => 'Great local coverage.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $subscriber->id,
        ]);

        $notification = $subscriber->fresh()->notifications()->latest()->first();

        $this->assertSame('submission_approved', data_get($notification?->data, 'type'));
        $this->assertSame('Your story is now live', data_get($notification?->data, 'title'));
    }

    public function test_submitter_receives_rejection_notification_after_review(): void
    {
        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();
        $county = County::query()->firstOrFail();

        $submission = UserSubmission::query()->create([
            'user_id' => $subscriber->id,
            'county_id' => $county->id,
            'title' => 'Road closure rumor',
            'location_label' => 'Butler County',
            'description' => 'A user-submitted road closure rumor needs verification.',
            'status' => UserSubmissionStatus::Pending->value,
        ]);

        app(SubmissionReviewService::class)->reject($submission, $admin, 'Please include a verified source for this update.');

        $notification = $subscriber->fresh()->notifications()->latest()->first();

        $this->assertSame('submission_rejected', data_get($notification?->data, 'type'));
        $this->assertStringContainsString('verified source', (string) data_get($notification?->data, 'body'));
    }
}
