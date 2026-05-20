<?php

namespace App\Providers;

use App\Repositories\Contracts\ArchiveRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\NewsPostRepositoryInterface;
use App\Repositories\Contracts\PendingRegistrationRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentArchiveRepository;
use App\Repositories\Eloquent\EloquentLocationRepository;
use App\Repositories\Eloquent\EloquentNewsPostRepository;
use App\Repositories\Eloquent\EloquentPendingRegistrationRepository;
use App\Repositories\Eloquent\EloquentSubmissionRepository;
use App\Repositories\Eloquent\EloquentSubscriptionRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Models\Advertisement;
use App\Models\ActivityLog;
use App\Models\ArchivePurchase;
use App\Models\County;
use App\Models\NewsPost;
use App\Models\Permission;
use App\Models\PostCategory;
use App\Models\PostArchive;
use App\Models\PostSubcategory;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubmission;
use App\Observers\NewsPostObserver;
use App\Policies\AdvertisementPolicy;
use App\Policies\ActivityLogPolicy;
use App\Policies\ArchivePurchasePolicy;
use App\Policies\CountyPolicy;
use App\Policies\NewsPostPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PostCategoryPolicy;
use App\Policies\PostArchivePolicy;
use App\Policies\PostSubcategoryPolicy;
use App\Policies\RolePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserSubmissionPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, EloquentLocationRepository::class);
        $this->app->bind(NewsPostRepositoryInterface::class, EloquentNewsPostRepository::class);
        $this->app->bind(PendingRegistrationRepositoryInterface::class, EloquentPendingRegistrationRepository::class);
        $this->app->bind(SubmissionRepositoryInterface::class, EloquentSubmissionRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
        $this->app->bind(ArchiveRepositoryInterface::class, EloquentArchiveRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(NewsPost::class, NewsPostPolicy::class);
        Gate::policy(UserSubmission::class, UserSubmissionPolicy::class);
        Gate::policy(Advertisement::class, AdvertisementPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(ArchivePurchase::class, ArchivePurchasePolicy::class);
        Gate::policy(County::class, CountyPolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(PostCategory::class, PostCategoryPolicy::class);
        Gate::policy(PostArchive::class, PostArchivePolicy::class);
        Gate::policy(PostSubcategory::class, PostSubcategoryPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('dashboard.view', fn (User $user): bool => $user->hasPermission('dashboard.view'));
        Gate::define('roles.manage', fn (User $user): bool => $user->hasPermission('roles.manage'));
        Gate::define('logs.view', fn (User $user): bool => $user->hasPermission('logs.view'));
        Gate::define('subscriptions.view', fn (User $user): bool => $user->hasPermission('subscriptions.view'));
        Gate::define('archives.view', fn (User $user): bool => $user->hasPermission('archives.view'));

        NewsPost::observe(NewsPostObserver::class);
    }
}
