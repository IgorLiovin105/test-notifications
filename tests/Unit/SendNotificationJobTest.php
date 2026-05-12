<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\NotificationChannels\ChannelManager;
use App\Services\NotificationChannels\EmailChannel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_marks_notification_as_sent_on_success(): void
    {
        $notification = Notification::create([
            'user_id' => 4,
            'message' => 'Test message',
            'channel' => 'email',
            'status' => NotificationStatus::PENDING->value,
            'retry_count' => 0,
        ]);

        $channelManager = $this->createMock(ChannelManager::class);
        $emailChannel = $this->createMock(EmailChannel::class);

        $channelManager->expects($this->once())
            ->method('getChannel')
            ->with('email')
            ->willReturn($emailChannel);

        $emailChannel->expects($this->once())
            ->method('send')
            ->with(4, 'Test message')
            ->willReturn(true);

        $job = new SendNotificationJob($notification);
        $job->handle($channelManager);

        $notification->refresh();

        $this->assertEquals(NotificationStatus::SENT->value, $notification->status);
        $this->assertNotNull($notification->sent_at);
    }
}
