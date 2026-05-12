<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_creates_notification_via_api()
    {
        $payload = [
            'user_id' => 2,
            'message' => 'Test notification',
            'channel' => 'email',
        ];

        $response = $this->postJson('/api/notifications', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'status']);

        $this->assertDatabaseHas('notifications', [
            'user_id' => 2,
            'message' => 'Test notification',
            'status' => NotificationStatus::PENDING->value,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/notifications', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'message', 'channel']);
    }

    /** @test */
    public function it_validates_channel_enum()
    {
        $payload = [
            'user_id' => 3,
            'message' => 'Test',
            'channel' => 'invalid_channel',
        ];

        $response = $this->postJson('/api/notifications', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    /** @test */
    public function it_returns_notification_status(): void
    {
        // Создаём уведомление напрямую, без фабрики
        $notification = Notification::create([
            'user_id' => '123',
            'message' => 'Test message',
            'channel' => 'email',
            'status' => NotificationStatus::PENDING->value,
            'retry_count' => 0,
        ]);

        $response = $this->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $notification->id,
                'status' => $notification->status,
                'user_id' => $notification->user_id,
                'message' => $notification->message,
                'channel' => $notification->channel,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_notification()
    {
        $response = $this->getJson('/api/notifications/?user_id=99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_user_history_with_filters(): void
    {
        $userId = 4;

        Notification::create([
            'user_id' => $userId,
            'message' => 'Sent email message',
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
            'retry_count' => 0,
        ]);

        Notification::create([
            'user_id' => $userId,
            'message' => 'Pending telegram message',
            'status' => NotificationStatus::PENDING->value,
            'channel' => 'telegram',
            'retry_count' => 0,
        ]);

        Notification::create([
            'user_id' => $userId,
            'message' => 'Another sent email',
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
            'retry_count' => 0,
        ]);

        Notification::create([
            'user_id' => '99',
            'message' => 'Other user message',
            'status' => NotificationStatus::SENT->value,
            'channel' => 'email',
            'retry_count' => 0,
        ]);

        $response = $this->getJson("/api/users/{$userId}/notifications?status=sent");
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $response = $this->getJson("/api/users/{$userId}/notifications?channel=telegram");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $response = $this->getJson("/api/users/{$userId}/notifications");
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'message',
                    'status',
                    'channel',
                    'created_at',
                ],
            ],
            'total',
            'per_page',
        ]);
    }
}
