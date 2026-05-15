<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ResendRegistrationOtpRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\VerifyRegistrationOtpRequest;
use App\Http\Resources\Api\AuthenticatedUserResource;
use App\Services\Auth\AuthService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse($result['data'], $result['message']);
    }

    public function verifyOtp(VerifyRegistrationOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyRegistrationOtp($request->validated());
        $result['user']->load(['profile', 'roles', 'locations.county', 'subscriptions', 'archivePurchases']);

        return $this->successResponse([
            'user' => (new AuthenticatedUserResource($result['user']))->resolve(),
            'token' => $result['token'],
        ], 'Registration completed successfully.', 201);
    }

    public function resendOtp(ResendRegistrationOtpRequest $request): JsonResponse
    {
        $result = $this->authService->resendRegistrationOtp($request->validated('email'));

        return $this->successResponse($result['data'], $result['message']);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());
        $result['user']->load(['profile', 'roles', 'locations.county', 'subscriptions', 'archivePurchases']);

        return $this->successResponse([
            'user' => (new AuthenticatedUserResource($result['user']))->resolve(),
            'token' => $result['token'],
        ], 'Login completed successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user(), (bool) $request->boolean('all_devices'));

        return $this->successResponse([], 'Logout completed successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'roles', 'locations.county.region.country', 'subscriptions', 'archivePurchases.county']);

        return $this->resourceResponse(new AuthenticatedUserResource($user), 'Profile fetched successfully.');
    }
}
