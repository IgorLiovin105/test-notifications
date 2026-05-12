<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\NotificationChannels\ChannelManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(private Notification $notification) {}

    public function handle(ChannelManager $channelManager): void
    {
        // Обновляем статус, если уведомление уже отправлено
        if ($this->notification->status === NotificationStatus::SENT->value) {
            return;
        }

        try {
            $channel = $channelManager->getChannel($this->notification->channel);
            $success = $channel->send(
                (int) $this->notification->user_id,
                $this->notification->message
            );

            if ($success) {
                $this->notification->update([
                    'status' => NotificationStatus::SENT->value,
                    'sent_at' => now(),
                ]);
                Log::info('Notification sent successfully', [
                    'notification_id' => $this->notification->id,
                ]);
            } else {
                throw new \Exception('Channel returned false');
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: ' . $e->getMessage(), [
                'notification_id' => $this->notification->id,
                'attempt' => $this->attempts(),
            ]);

            // Увеличиваем счетчик попыток
            $this->notification->increment('retry_count');

            // Обновляем статус после достижения максимального количества попыток
            if ($this->attempts() >= $this->tries) {
                $this->notification->update([
                    'status' => NotificationStatus::FAILED->value,
                ]);
                Log::warning("Notification permanently failed after {$this->tries} attempts", [
                    'notification_id' => $this->notification->id,
                ]);

                return;
            }

            // Повторяем попытку с задержкой
            $this->release($this->backoff);
        }
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
