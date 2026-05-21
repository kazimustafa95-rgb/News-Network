<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
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

    public function test_profile_update_can_change_name_and_email(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->putJson('/api/profile', [
            'name' => 'Ray Butler',
            'email' => 'ray.butler@example.test',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Ray Butler')
            ->assertJsonPath('data.email', 'ray.butler@example.test')
            ->assertJsonPath('data.profile.first_name', 'Ray')
            ->assertJsonPath('data.profile.last_name', 'Butler');
    }

    public function test_profile_update_can_upload_avatar_image(): void
    {
        $diskRoot = base_path('tests/.tmp/profile-disk');

        File::deleteDirectory($diskRoot);
        File::ensureDirectoryExists($diskRoot);

        config()->set('filesystems.disks.profile-testing', [
            'driver' => 'local',
            'root' => $diskRoot,
            'url' => '/tests/profile-disk',
            'visibility' => 'public',
        ]);
        config()->set('community_will.media.profile_disk', 'profile-testing');

        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->put('/api/profile', [
            'name' => 'Butler Subscriber',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.profile.first_name', 'Butler');

        $subscriber->refresh()->load('profile');

        $this->assertNotNull($subscriber->profile?->avatar_path);
        $this->assertNotNull($subscriber->profile?->avatar_url);
        Storage::disk('profile-testing')->assertExists($subscriber->profile->avatar_path);
    }

    public function test_profile_update_accepts_string_boolean_for_remove_avatar_in_multipart_requests(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->post('/api/profile', [
            '_method' => 'PUT',
            'name' => 'String Boolean User',
            'remove_avatar' => 'false',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'String Boolean User');
    }

    public function test_profile_delete_soft_deletes_user_and_profile(): void
    {
        $subscriber = User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        $subscriber->forceFill([
            'password' => Hash::make('password'),
        ])->save();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->deleteJson('/api/profile', [
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $subscriber->id]);
        $this->assertSoftDeleted('user_profiles', ['user_id' => $subscriber->id]);
    }
}
