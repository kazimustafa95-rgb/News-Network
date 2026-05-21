<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeleteProfileRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\AuthenticatedUserResource;
use App\Services\Profile\ProfileService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ProfileService $profiles)
    {
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profiles->update($request->user(), $request->validated());

        return $this->resourceResponse(new AuthenticatedUserResource($user), 'Profile updated successfully.');
    }

    public function destroy(DeleteProfileRequest $request): JsonResponse
    {
        $this->profiles->delete($request->user());

        return $this->successResponse([], 'Profile deleted successfully.');
    }
}
