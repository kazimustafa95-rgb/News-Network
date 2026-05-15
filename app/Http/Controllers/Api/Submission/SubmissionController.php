<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubmissionRequest;
use App\Http\Resources\Api\SubmissionResource;
use App\Services\Submission\SubmissionService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SubmissionService $submissions)
    {
    }

    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        $submission = $this->submissions->store($request->user(), $request->validated());

        return $this->resourceResponse(new SubmissionResource($submission), 'Submission created successfully.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $submissions = $this->submissions->paginateForUser($request->user(), $request->only(['status', 'per_page']));

        return response()->json([
            'success' => true,
            'message' => 'Data fetched successfully.',
            'data' => SubmissionResource::collection($submissions->getCollection())->resolve(),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
                'last_page' => $submissions->lastPage(),
            ],
        ]);
    }
}
