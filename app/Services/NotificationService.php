<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function createNotification(int $user_id, string $message, string $channel): Notification
    {
        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'user_id' => $user_id,
                'message' => substr($message, 0, 500),
                'channel' => $channel,
                'status' => NotificationStatus::PENDING->value,
                'retry_count' => 0,
            ]);

            SendNotificationJob::dispatch($notification);

            DB::commit();

            return $notification;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create notification: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getStatus(int $notification_id): ?Notification
    {
        return Notification::find($notification_id);
    }

    public function getUserHistory(int $user_id, ?string $status = null, ?string $channel = null)
    {
        $query = Notification::where('user_id', $user_id)->with('user');

        if ($status) {
            $query->where('status', $status);
        }
        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->latest()->paginate(20);
    }
}
