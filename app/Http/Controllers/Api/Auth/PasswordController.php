<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PasswordResetService $passwordResetService)
    {
    }

    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->sendResetLink($request->validated('email'));

        return $this->successResponse([], 'Password reset email sent successfully.');
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->reset($request->validated());

        return $this->successResponse([], 'Password reset completed successfully.');
    }
}
