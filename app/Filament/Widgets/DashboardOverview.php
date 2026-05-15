<?php

namespace App\Filament\Widgets;

use App\Services\Admin\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $metrics = app(DashboardService::class)->metrics();

        return [
            Stat::make('Total Users', (string) $metrics['total_users']),
            Stat::make('Saved Locations', (string) $metrics['saved_locations']),
            Stat::make('Posts', (string) $metrics['posts_count']),
            Stat::make('Archived Posts', (string) $metrics['archived_posts']),
            Stat::make('Pending Submissions', (string) $metrics['pending_submissions']),
            Stat::make('Active Subscriptions', (string) $metrics['active_subscriptions']),
            Stat::make('Archive Purchases', (string) $metrics['archive_purchases']),
            Stat::make('Active Ads', (string) $metrics['active_ads']),
        ];
    }
}
