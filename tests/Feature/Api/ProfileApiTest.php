<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $subscriber = User::query()->where('email', 'subscriber@communitywill.test')->firstOrFail();

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
}
