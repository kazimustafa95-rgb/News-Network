<?php

namespace Tests\Feature\Api;

use App\Enums\ArchivePurchaseStatus;
use App\Enums\NewsPostStatus;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedArchiveApiTest extends TestCase
{
    use RefreshDatabase;

    protected County $county;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            GeographySeeder::class,
            AdminUserSeeder::class,
            DemoContentSeeder::class,
        ]);

        $this->county = County::query()->where('slug', 'butler-county')->firstOrFail();
    }

    public function test_feed_supports_search_filtering(): void
    {
        $response = $this->getJson('/api/feed?county_id='.$this->county->id.'&search=update 1');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $timeline = $response->json('data.timeline');

        $this->assertNotEmpty($timeline);
        $this->assertStringContainsString('update 1', strtolower($timeline[0]['title']));
        $this->assertNotEmpty($response->json('data.ads'));
    }

    public function test_feed_includes_timeline_advertisement_items(): void
    {
        $response = $this->getJson('/api/feed?county_id='.$this->county->id);

        $response->assertOk();

        $this->assertContains('advertisement', array_column($response->json('data.timeline_items'), 'type'));
    }

    public function test_archive_index_returns_locked_entitlement_state_for_guest(): void
    {
        $archiveDate = now()->subDays(10)->toDateString();

        $response = $this->getJson('/api/archive?county_id='.$this->county->id.'&archive_date='.$archiveDate);

        $response
            ->assertOk()
            ->assertJsonPath('data.entitlement.has_access', false)
            ->assertJsonPath('data.entitlement.purchase_required', true);
    }

    public function test_archived_post_detail_requires_a_purchase(): void
    {
        $archivedPost = NewsPost::query()
            ->where('status', NewsPostStatus::Archived->value)
            ->firstOrFail();

        $this->getJson('/api/feed/'.$archivedPost->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'Archive purchase required to view this story.');
    }

    public function test_purchased_archived_post_can_be_viewed_by_entitled_user(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();
        $archivedPost = NewsPost::query()
            ->where('status', NewsPostStatus::Archived->value)
            ->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $this->getJson('/api/feed/'.$archivedPost->id)
            ->assertOk()
            ->assertJsonPath('data.id', $archivedPost->id);
    }

    public function test_purchase_history_returns_archived_posts_for_authenticated_user(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->getJson('/api/purchases');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data'));
        $this->assertNotNull($response->json('data.0.purchase_date'));
        $this->assertNotNull($response->json('data.0.post.id'));
    }
}
