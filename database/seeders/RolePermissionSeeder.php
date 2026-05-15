<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'group' => 'users'],
            ['name' => 'Manage Counties', 'slug' => 'counties.manage', 'group' => 'counties'],
            ['name' => 'Manage Posts', 'slug' => 'posts.manage', 'group' => 'posts'],
            ['name' => 'Review Submissions', 'slug' => 'submissions.review', 'group' => 'submissions'],
            ['name' => 'Manage Ads', 'slug' => 'ads.manage', 'group' => 'advertisements'],
            ['name' => 'View Archives', 'slug' => 'archives.view', 'group' => 'archives'],
            ['name' => 'View Subscriptions', 'slug' => 'subscriptions.view', 'group' => 'subscriptions'],
            ['name' => 'View Logs', 'slug' => 'logs.view', 'group' => 'logs'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'roles'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::query()->updateOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData,
            );
        }

        $roles = [
            RoleSlug::User->value => ['dashboard.view'],
            RoleSlug::Subscriber->value => ['dashboard.view'],
            RoleSlug::Moderator->value => ['dashboard.view', 'submissions.review'],
            RoleSlug::Editor->value => ['dashboard.view', 'counties.manage', 'posts.manage', 'submissions.review', 'ads.manage', 'archives.view', 'subscriptions.view', 'logs.view'],
            RoleSlug::SuperAdmin->value => array_column($permissions, 'slug'),
        ];

        foreach ($roles as $slug => $permissionSlugs) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => str($slug)->replace('_', ' ')->title()->value(), 'description' => str($slug)->replace('_', ' ')->title()->value()],
            );

            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $permissionSlugs)->pluck('id')->all(),
            );
        }
    }
}
