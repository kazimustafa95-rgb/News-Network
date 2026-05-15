<?php

namespace App\Support\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = [],
        string $message = 'Request completed successfully.',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function paginatedResponse(
        LengthAwarePaginator|AnonymousResourceCollection $paginator,
        string $message = 'Data fetched successfully.'
    ): JsonResponse {
        if ($paginator instanceof AnonymousResourceCollection) {
            $resource = $paginator->resource;
            if (! $resource instanceof LengthAwarePaginator) {
                return $this->successResponse($paginator->resolve(), $message);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $paginator->resolve(),
                'meta' => [
                    'current_page' => $resource->currentPage(),
                    'per_page' => $resource->perPage(),
                    'total' => $resource->total(),
                    'last_page' => $resource->lastPage(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    protected function resourceResponse(
        JsonResource $resource,
        string $message = 'Data fetched successfully.',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource->resolve(),
        ], $status);
    }

    protected function errorResponse(
        string $message = 'Validation failed.',
        array $errors = [],
        int $status = 422
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
