<?php

namespace App\Services\Admin;

use App\Models\Advertisement;
use App\Models\ArchivePurchase;
use App\Models\NewsPost;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserSubmission;

class DashboardService
{
    public function metrics(): array
    {
        return [
            'total_users' => User::query()->count(),
            'saved_locations' => UserLocation::query()->count(),
            'posts_count' => NewsPost::query()->count(),
            'archived_posts' => NewsPost::query()->where('status', 'archived')->count(),
            'pending_submissions' => UserSubmission::query()->where('status', 'pending')->count(),
            'active_subscriptions' => Subscription::query()->where('status', 'active')->count(),
            'archive_purchases' => ArchivePurchase::query()->count(),
            'active_ads' => Advertisement::query()->where('status', 'active')->count(),
        ];
    }
}
