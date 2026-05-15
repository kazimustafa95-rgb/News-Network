<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function update(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $profile = $user->profile()->firstOrCreate();
            $displayName = trim((string) ($attributes['name'] ?? ''));

            if ($displayName !== '') {
                $user->name = $displayName;
            } elseif (filled($attributes['first_name'] ?? null) || filled($attributes['last_name'] ?? null)) {
                $user->name = trim(implode(' ', array_filter([
                    $attributes['first_name'] ?? $profile->first_name,
                    $attributes['last_name'] ?? $profile->last_name,
                ])));
            }

            if (filled($attributes['email'] ?? null)) {
                $user->email = (string) $attributes['email'];
            }

            $nameParts = $displayName !== ''
                ? preg_split('/\s+/', $displayName) ?: []
                : [];

            if (($attributes['avatar'] ?? null) instanceof UploadedFile) {
                $attributes['avatar_path'] = $attributes['avatar']->store('profiles/avatars', config('community_will.media.profile_disk'));
            }

            $user->save();

            $profile->fill([
                'first_name' => $attributes['first_name'] ?? ($nameParts[0] ?? $profile->first_name),
                'last_name' => $attributes['last_name'] ?? (count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $profile->last_name),
                'phone' => $attributes['phone'] ?? null,
                'avatar_path' => $attributes['avatar_path'] ?? $profile->avatar_path,
                'onboarding_completed_at' => $profile->onboarding_completed_at ?? now(),
            ])->save();

            $user->refresh();
            $user->load(['profile', 'locations.county', 'subscriptions', 'archivePurchases']);

            return $user;
        });
    }
}
