<?php

namespace Tests\Feature\Api;

use App\Enums\NewsPostStatus;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\User;
use App\Notifications\NewsPublishedNotification;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
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
        ]);

        $this->county = County::query()->where('slug', 'butler-county')->firstOrFail();
    }

    public function test_user_can_register_a_notification_device(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->postJson('/api/notifications/device-token', [
            'token' => 'ios-device-token-123',
            'platform' => 'ios',
            'device_name' => 'iPhone 15',
            'app_version' => '1.0.0',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.is_registered', true)
            ->assertJsonPath('data.platform', 'ios');

        $this->assertDatabaseHas('user_notification_devices', [
            'user_id' => $subscriber->id,
            'platform' => 'ios',
            'is_active' => true,
        ]);
    }

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $post = NewsPost::withoutEvents(fn () => NewsPost::query()->create([
            'county_id' => $this->county->id,
            'author_id' => $subscriber->id,
            'title' => 'Library renovation approved',
            'slug' => 'library-renovation-approved',
            'excerpt' => 'Renovation work at the county library begins next month.',
            'body' => 'Renovation work at the county library begins next month.',
            'topic' => 'community',
            'source_type' => 'admin_original',
            'status' => NewsPostStatus::Published->value,
            'published_at' => now(),
        ]));

        $subscriber->notify(new NewsPublishedNotification($post->load('county')));

        $indexResponse = $this->getJson('/api/notifications');

        $notificationId = $indexResponse->json('data.0.id');

        $indexResponse
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('data.0.title', 'New story available')
            ->assertJsonPath('data.0.date_group', 'Today');

        $markReadResponse = $this->postJson(sprintf('/api/notifications/%s/read', $notificationId));

        $markReadResponse
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_user_can_list_notifications_with_string_boolean_query_value(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->getJson('/api/notifications?per_page=20&unread_only=false');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 20);
    }
}
