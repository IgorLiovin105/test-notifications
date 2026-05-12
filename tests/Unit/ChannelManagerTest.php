<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\NotificationChannels\ChannelManager;
use App\Services\NotificationChannels\EmailChannel;
use InvalidArgumentException;
use Tests\TestCase;

class ChannelManagerTest extends TestCase
{
    /** @test */
    public function it_registers_and_retrieves_channels()
    {
        $manager = new ChannelManager;
        $emailChannel = new EmailChannel;

        $manager->register('email', $emailChannel);

        $this->assertSame($emailChannel, $manager->getChannel('email'));
    }

    /** @test */
    public function it_throws_exception_for_unknown_channel()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new ChannelManager;
        $manager->getChannel('unknown');
    }
}
