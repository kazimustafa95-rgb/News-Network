<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DestroyNotificationDeviceRequest;
use App\Http\Requests\Api\IndexNotificationsRequest;
use App\Http\Requests\Api\StoreNotificationDeviceRequest;
use App\Http\Resources\Api\NotificationResource;
use App\Services\Notification\NotificationService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(IndexNotificationsRequest $request): JsonResponse
    {
        $notifications = $this->notifications->paginateForUser($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Notifications fetched successfully.',
            'data' => NotificationResource::collection($notifications->getCollection())->resolve(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $this->notifications->unreadCount($request->user()),
            ],
        ]);
    }

    public function storeDeviceToken(StoreNotificationDeviceRequest $request): JsonResponse
    {
        $device = $this->notifications->registerDevice($request->user(), $request->validated());

        return $this->successResponse([
            'device_id' => $device->id,
            'platform' => $device->platform,
            'is_registered' => true,
            'last_seen_at' => optional($device->last_seen_at)->toIso8601String(),
        ], 'Device registered for notifications successfully.');
    }

    public function destroyDeviceToken(DestroyNotificationDeviceRequest $request): JsonResponse
    {
        $this->notifications->deactivateDevice($request->user(), (string) $request->validated('token'));

        return $this->successResponse([
            'is_registered' => false,
        ], 'Device removed from notifications successfully.');
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $record = $this->notifications->findForUser($request->user(), $notification);

        abort_if($record === null, 404);

        if (! $record->read_at) {
            $record->markAsRead();
        }

        return $this->resourceResponse(new NotificationResource($record->fresh()), 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notifications->markAllAsRead($request->user());

        return $this->successResponse([
            'unread_count' => 0,
        ], 'All notifications marked as read.');
    }
}
