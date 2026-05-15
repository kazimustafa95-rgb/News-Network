<?php

namespace Tests\Feature\Admin;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
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

    public function test_guest_is_redirected_to_the_filament_login_screen(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_staff_roles_can_access_the_admin_panel_but_subscribers_cannot(): void
    {
        $panel = Filament::getPanel('admin');
        $admin = \App\Models\User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        $editor = \App\Models\User::query()->where('email', 'editor@gmail.com')->firstOrFail();
        $moderator = \App\Models\User::query()->where('email', 'moderator@gmail.com')->firstOrFail();
        $subscriber = \App\Models\User::query()->where('email', 'subscriber@gmail.com')->firstOrFail();

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertTrue($editor->canAccessPanel($panel));
        $this->assertTrue($moderator->canAccessPanel($panel));
        $this->assertFalse($subscriber->canAccessPanel($panel));
    }
}
