<?php

namespace App\Http\Controllers\Api\Feed;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdvertisementCardResource;
use App\Http\Requests\Api\FeedIndexRequest;
use App\Http\Resources\Api\CountyResource;
use App\Http\Resources\Api\FeedPostCardResource;
use App\Http\Resources\Api\FeedPostDetailResource;
use App\Services\Feed\FeedService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FeedService $feed)
    {
    }

    public function index(FeedIndexRequest $request): JsonResponse
    {
        $payload = $this->feed->getFeed($request->validated());
        $timeline = FeedPostCardResource::collection($payload['timeline']->getCollection())->resolve();
        $ads = AdvertisementCardResource::collection($payload['ads'])->resolve();

        return response()->json([
            'success' => true,
            'message' => 'Data fetched successfully.',
            'data' => [
                'county' => (new CountyResource($payload['county']))->resolve(),
                'featured' => FeedPostCardResource::collection($payload['featured'])->resolve(),
                'breaking' => FeedPostCardResource::collection($payload['breaking'])->resolve(),
                'ads' => $ads,
                'timeline' => $timeline,
                'timeline_items' => $this->buildTimelineItems($timeline, $ads, (int) $payload['ad_interval']),
            ],
            'meta' => [
                'current_page' => $payload['timeline']->currentPage(),
                'per_page' => $payload['timeline']->perPage(),
                'total' => $payload['timeline']->total(),
                'last_page' => $payload['timeline']->lastPage(),
            ],
        ]);
    }

    public function availableCounties(Request $request): JsonResponse
    {
        return $this->successResponse(
            CountyResource::collection($this->feed->getAvailableCounties($request->only('search')))->resolve(),
            'Counties fetched successfully.'
        );
    }

    public function show(int $post, Request $request): JsonResponse
    {
        return $this->resourceResponse(
            new FeedPostDetailResource($this->feed->getPost($post, $request->user('api'))->load(['county', 'author', 'videos', 'archive'])),
            'Post fetched successfully.'
        );
    }

    private function buildTimelineItems(array $timeline, array $ads, int $adInterval): array
    {
        if ($timeline === [] || $ads === [] || $adInterval < 1) {
            return array_map(fn (array $post) => [
                'type' => 'post',
                'data' => $post,
            ], $timeline);
        }

        $items = [];
        $adIndex = 0;
        $adCount = count($ads);

        foreach ($timeline as $index => $post) {
            $items[] = [
                'type' => 'post',
                'data' => $post,
            ];

            if ((($index + 1) % $adInterval) === 0) {
                $items[] = [
                    'type' => 'advertisement',
                    'data' => $ads[$adIndex % $adCount],
                ];

                $adIndex++;
            }
        }

        return $items;
    }
}
