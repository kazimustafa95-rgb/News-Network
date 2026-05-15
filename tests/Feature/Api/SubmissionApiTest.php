<?php

namespace Tests\Feature\Api;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\County;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionApiTest extends TestCase
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

    public function test_subscriber_can_submit_news_with_video(): void
    {
        Storage::fake('public');

        $subscriber = User::query()->where('email', 'subscriber@communitywill.test')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->post('/api/submissions', [
            'county_id' => $this->county->id,
            'title' => 'Severe weather update',
            'location_label' => 'Greenville, Butler County',
            'description' => 'Severe weather update from Greenville.',
            'media' => UploadedFile::fake()->create('weather-update.mp4', 2048, 'video/mp4'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('user_submissions', [
            'user_id' => $subscriber->id,
            'county_id' => $this->county->id,
            'title' => 'Severe weather update',
            'location_label' => 'Greenville, Butler County',
            'status' => 'pending',
        ]);
    }

    public function test_non_subscriber_cannot_submit_news(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);

        $user->roles()->sync(
            Role::query()->where('slug', RoleSlug::User->value)->pluck('id')->all(),
        );

        Sanctum::actingAs($user, [], 'api');

        $response = $this->post('/api/submissions', [
            'county_id' => $this->county->id,
            'title' => 'Local update without subscription',
            'location_label' => 'Butler County',
            'description' => 'Local update without subscription.',
            'media' => UploadedFile::fake()->create('local-update.mp4', 1024, 'video/mp4'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'An active subscription is required for this action.');
    }

    public function test_subscriber_can_submit_news_with_image_media(): void
    {
        Storage::fake('public');

        $subscriber = User::query()->where('email', 'subscriber@communitywill.test')->firstOrFail();

        Sanctum::actingAs($subscriber, [], 'api');

        $response = $this->post('/api/submissions', [
            'county_id' => $this->county->id,
            'title' => 'School board meeting recap',
            'location_label' => 'Georgiana, Butler County',
            'description' => 'Photo recap from the school board meeting.',
            'media' => UploadedFile::fake()->image('school-board.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.media.0.kind', 'image')
            ->assertJsonPath('data.media.0.processing_status', 'ready');
    }
}
