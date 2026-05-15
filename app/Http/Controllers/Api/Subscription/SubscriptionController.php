<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifySubscriptionRequest;
use App\Http\Resources\Api\SubscriptionStatusResource;
use App\Services\Subscription\SubscriptionService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    public function status(Request $request): JsonResponse
    {
        return $this->successResponse(
            [
                'subscription' => (new SubscriptionStatusResource($this->subscriptions->status($request->user())))->resolve(),
                'is_active' => $this->subscriptions->hasActiveEntitlement($request->user()),
                'submission_enabled' => $this->subscriptions->hasActiveEntitlement($request->user()),
            ],
            'Subscription status fetched successfully.'
        );
    }

    public function verify(VerifySubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptions->verify($request->user(), $request->validated());

        return $this->successResponse([
            'subscription' => (new SubscriptionStatusResource($subscription))->resolve(),
            'is_active' => $this->subscriptions->hasActiveEntitlement($request->user()),
            'submission_enabled' => $this->subscriptions->hasActiveEntitlement($request->user()),
        ], 'Subscription verified successfully.', 201);
    }
}
