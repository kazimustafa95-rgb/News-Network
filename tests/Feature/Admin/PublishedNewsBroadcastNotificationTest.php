<?php

namespace Tests\Feature\Admin;

use App\Enums\NewsPostStatus;
use App\Enums\UserStatus;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishedNewsBroadcastNotificationTest extends TestCase
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

    public function test_published_admin_post_creates_notifications_for_active_users(): void
    {
        $county = County::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        $inactiveUser = User::factory()->create([
            'status' => UserStatus::Inactive->value,
        ]);

        NewsPost::query()->create([
            'county_id' => $county->id,
            'author_id' => $admin->id,
            'title' => 'City council approves new park',
            'slug' => 'city-council-approves-new-park',
            'excerpt' => 'A new public park project has been approved for downtown.',
            'body' => 'A new public park project has been approved for downtown.',
            'topic' => 'community',
            'source_type' => 'admin_original',
            'status' => NewsPostStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $subscriber->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $inactiveUser->id,
        ]);
    }
}
