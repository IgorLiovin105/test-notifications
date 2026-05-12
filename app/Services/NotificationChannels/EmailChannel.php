<?php

declare(strict_types=1);

namespace App\Services\NotificationChannels;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmailChannel implements ChannelInterface
{
    public function send(int $user_id, string $message): bool
    {
        try {
            $user = User::find($user_id);

            if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                Log::error("Invalid email address: {$user->email}");

                return false;
            }

            Log::info("Sending email to {$user->email} with message: {$message}");

            // Код отправки - сейчас заглушка

            return true;
        } catch (\Exception $e) {
            Log::error("Telegram send failed for user {$user_id}: " . $e->getMessage());
            throw new \Exception('Failed to send Telegram notification: ' . $e->getMessage());
        }
    }
}
