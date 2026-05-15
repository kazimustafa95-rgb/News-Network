<?php

namespace App\Http\Middleware;

use App\Services\Subscription\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->subscriptionService->hasActiveEntitlement($user)) {
            abort(403, 'An active subscription is required for this action.');
        }

        return $next($request);
    }
}
