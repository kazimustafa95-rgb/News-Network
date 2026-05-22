<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetPageController extends Controller
{
    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'completed' => false,
            'email' => (string) $request->query('email', ''),
            'status' => session('status'),
            'token' => $token,
        ]);
    }

    public function update(ResetPasswordRequest $request, PasswordResetService $passwordResetService): View
    {
        $passwordResetService->reset($request->validated());

        return view('auth.reset-password', [
            'completed' => true,
            'email' => $request->string('email')->toString(),
            'status' => 'Password reset completed successfully.',
            'token' => '',
        ]);
    }
}
