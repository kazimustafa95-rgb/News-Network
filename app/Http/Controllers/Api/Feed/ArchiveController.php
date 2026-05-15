<?php

namespace App\Http\Controllers\Api\Feed;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ArchiveIndexRequest;
use App\Http\Requests\Api\ArchivePurchaseRequest;
use App\Http\Requests\Api\PurchaseHistoryRequest;
use App\Http\Resources\Api\ArchivePurchaseResource;
use App\Http\Resources\Api\CountyResource;
use App\Http\Resources\Api\FeedPostCardResource;
use App\Http\Resources\Api\PurchasedArchivePostResource;
use App\Services\Archive\ArchiveService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ArchiveController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ArchiveService $archives)
    {
    }

    public function index(ArchiveIndexRequest $request): JsonResponse
    {
        $payload = $this->archives->index($request->validated(), $request->user('api'));
        $posts = FeedPostCardResource::collection($payload['posts']->getCollection())->resolve();

        return response()->json([
            'success' => true,
            'message' => 'Data fetched successfully.',
            'data' => [
                'county' => (new CountyResource($payload['county']))->resolve(),
                'archive_date' => $payload['archive_date'],
                'entitlement' => $payload['entitlement'],
                'posts' => $posts,
            ],
            'meta' => [
                'current_page' => $payload['posts']->currentPage(),
                'per_page' => $payload['posts']->perPage(),
                'total' => $payload['posts']->total(),
                'last_page' => $payload['posts']->lastPage(),
            ],
        ]);
    }

    public function purchase(ArchivePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->archives->purchase($request->user(), $request->validated());

        return $this->resourceResponse(new ArchivePurchaseResource($purchase), 'Archive purchase verified successfully.', 201);
    }

    public function history(PurchaseHistoryRequest $request): JsonResponse
    {
        $purchases = $this->archives->purchaseHistory($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data fetched successfully.',
            'data' => PurchasedArchivePostResource::collection($purchases->getCollection())->resolve(),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
                'last_page' => $purchases->lastPage(),
            ],
        ]);
    }
}
