<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\County;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $butlerCounty = County::query()->where('slug', 'butler-county')->firstOrFail();

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Community Will Admin',
                'password' => 'password',
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ],
        );

        $superAdmin->profile()->updateOrCreate([], [
            'first_name' => 'Community',
            'last_name' => 'Admin',
            'default_county_id' => $butlerCounty->id,
            'default_region_id' => $butlerCounty->region_id,
            'default_country_id' => $butlerCounty->region->country_id,
            'onboarding_completed_at' => now(),
        ]);

        $superAdmin->roles()->sync(Role::query()->where('slug', RoleSlug::SuperAdmin->value)->pluck('id'));

        $editor = User::query()->updateOrCreate(
            ['email' => 'editor@gmail.com'],
            [
                'name' => 'County Editor',
                'password' => 'password',
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ],
        );

        $editor->profile()->updateOrCreate([], [
            'first_name' => 'County',
            'last_name' => 'Editor',
            'default_county_id' => $butlerCounty->id,
            'default_region_id' => $butlerCounty->region_id,
            'default_country_id' => $butlerCounty->region->country_id,
            'onboarding_completed_at' => now(),
        ]);

        $editor->roles()->sync(Role::query()->where('slug', RoleSlug::Editor->value)->pluck('id'));

        $moderator = User::query()->updateOrCreate(
            ['email' => 'moderator@gmail.com'],
            [
                'name' => 'Submission Moderator',
                'password' => 'password',
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ],
        );

        $moderator->profile()->updateOrCreate([], [
            'first_name' => 'Submission',
            'last_name' => 'Moderator',
            'default_county_id' => $butlerCounty->id,
            'default_region_id' => $butlerCounty->region_id,
            'default_country_id' => $butlerCounty->region->country_id,
            'onboarding_completed_at' => now(),
        ]);

        $moderator->roles()->sync(Role::query()->where('slug', RoleSlug::Moderator->value)->pluck('id'));

        $subscriber = User::query()->updateOrCreate(
            ['email' => 'subscriber@gmail.com'],
            [
                'name' => 'Butler Subscriber',
                'password' => 'password',
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ],
        );

        $subscriber->profile()->updateOrCreate([], [
            'first_name' => 'Butler',
            'last_name' => 'Subscriber',
            'default_county_id' => $butlerCounty->id,
            'default_region_id' => $butlerCounty->region_id,
            'default_country_id' => $butlerCounty->region->country_id,
            'onboarding_completed_at' => now(),
        ]);

        $subscriber->roles()->sync(Role::query()->whereIn('slug', [RoleSlug::User->value, RoleSlug::Subscriber->value])->pluck('id'));
        $subscriber->locations()->updateOrCreate(
            ['county_id' => $butlerCounty->id],
            [
                'country_id' => $butlerCounty->region->country_id,
                'region_id' => $butlerCounty->region_id,
                'is_default' => true,
                'source' => 'manual',
            ],
        );

        $subscriber->subscriptions()->updateOrCreate(
            ['provider_transaction_id' => 'sub_demo_txn_001'],
            [
                'provider' => 'manual',
                'provider_product_id' => 'community_will_monthly',
                'plan_code' => 'monthly',
                'status' => SubscriptionStatus::Active->value,
                'started_at' => now()->subWeek(),
                'ends_at' => now()->addWeeks(3),
                'verified_at' => now()->subWeek(),
                'auto_renew' => true,
            ],
        );
    }
}
