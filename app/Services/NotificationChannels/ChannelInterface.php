<?php

declare(strict_types=1);

namespace App\Services\NotificationChannels;

interface ChannelInterface
{
    public function send(int $user_id, string $message): bool;
}
