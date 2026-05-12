<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
        Queue::fake();
    }

    /** @test */
    public function it_creates_notification_and_dispatches_job()
    {
        $user_id = 2;
        $message = 'Test message';
        $channel = 'email';

        $notification = $this->service->createNotification($user_id, $message, $channel);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user_id,
            'message' => $message,
            'channel' => $channel,
            'status' => NotificationStatus::PENDING->value,
        ]);

        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($notification) {
            return $job->getNotification()->id === $notification->id;
        });
    }

    /** @test */
    public function it_truncates_message_to_500_characters()
    {
        $longMessage = str_repeat('a', 600);

        $notification = $this->service->createNotification(5, $longMessage, 'email');

        $this->assertEquals(500, strlen($notification->message));
    }

    /** @test */
    public function it_returns_notification_status()
    {
        // Создаём уведомление с полным набором полей
        $notification = Notification::create([
            'user_id' => 8,
            'message' => 'Test message',
            'channel' => 'email',
            'status' => NotificationStatus::PENDING->value,
            'retry_count' => 0,
        ]);

        $found = $this->service->getStatus($notification->id);

        $this->assertEquals($notification->id, $found->id);
        $this->assertEquals($notification->status, $found->status);
        $this->assertEquals($notification->message, $found->message);
        $this->assertEquals($notification->user_id, $found->user_id);
    }

    /** @test */
    public function it_returns_user_history_with_filters()
    {
        $user_id = 5;

        Notification::create([
            'user_id' => $user_id,
            'message' => 'Test email message',
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
            'retry_count' => 0,
        ]);

        Notification::create([
            'user_id' => $user_id,
            'message' => 'Test telegram message',
            'status' => NotificationStatus::PENDING->value,
            'channel' => 'telegram',
            'retry_count' => 0,
        ]);

        Notification::create([
            'user_id' => 2,
            'message' => 'Another user message',
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
            'retry_count' => 0,
        ]);

        $filtered = $this->service->getUserHistory($user_id, NotificationStatus::SENT->value);
        $this->assertEquals(1, $filtered->total());

        $filtered = $this->service->getUserHistory($user_id, null, 'telegram');
        $this->assertEquals(1, $filtered->total());

        $all = $this->service->getUserHistory($user_id);
        $this->assertEquals(2, $all->total());
    }
}
