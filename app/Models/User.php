<?php

namespace App\Models;

use App\Enums\RoleSlug;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(UserLocation::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(UserSubmission::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'author_id');
    }

    public function archivePurchases(): HasMany
    {
        return $this->hasMany(ArchivePurchase::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function adminActions(): HasMany
    {
        return $this->hasMany(AdminAction::class, 'admin_id');
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions
            ->sortByDesc('ends_at')
            ->first(fn (Subscription $subscription): bool => in_array(
                $subscription->status,
                [SubscriptionStatus::Active, SubscriptionStatus::Grace],
                true,
            ));
    }

    public function hasRole(string|RoleSlug $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return $this->roles->contains(fn (Role $userRole): bool => $userRole->slug === $slug);
    }

    public function hasAnyRole(array $roles): bool
    {
        $normalized = array_map(
            fn (string|RoleSlug $role): string => $role instanceof RoleSlug ? $role->value : $role,
            $roles,
        );

        return $this->roles->contains(fn (Role $userRole): bool => in_array($userRole->slug, $normalized, true));
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->whereIn('slug', $permissions))
            ->exists();
    }

    public function isStaff(): bool
    {
        return $this->roles()
            ->whereNotIn('slug', [RoleSlug::User->value, RoleSlug::Subscriber->value])
            ->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->status === UserStatus::Active
            && $this->isStaff();
    }
}
