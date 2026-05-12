<?php

declare(strict_types=1);

namespace App\Services\NotificationChannels;

use InvalidArgumentException;

class ChannelManager
{
    protected array $channels = [];

    public function register(string $name, ChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function getChannel(string $name): ChannelInterface
    {
        if (! isset($this->channels[$name])) {
            throw new InvalidArgumentException("Channel [{$name}] not supported");
        }

        return $this->channels[$name];
    }
}
