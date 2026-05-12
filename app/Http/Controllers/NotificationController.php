<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GetNotificationRequest;
use App\Http\Requests\HistoryNotificationRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service)
    {
        $this->service = $service;
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $notification = $this->service->createNotification(
            (int) $data['user_id'],
            $data['message'],
            $data['channel']
        );

        return response()->json([
            'id' => $notification->id,
            'status' => $notification->status,
        ], 200);
    }

    public function show(GetNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $notification = $this->service->getStatus($data['user_id']);
        if (! $notification) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'notification' => $notification,
        ], 200);
    }

    public function history(HistoryNotificationRequest $request)
    {
        $data = $request->validated();

        $notifications = $this->service->getUserHistory($data['user_id'], $data['status'], $data['channel']);

        return response()->json($notifications);
    }
}
