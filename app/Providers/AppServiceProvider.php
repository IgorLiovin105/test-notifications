<?php

namespace App\Providers;

use App\Services\NotificationChannels\ChannelManager;
use App\Services\NotificationChannels\EmailChannel;
use App\Services\NotificationChannels\TelegramChannel;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            $manager = new ChannelManager;
            $manager->register('email', new EmailChannel);
            $manager->register('telegram', new TelegramChannel);

            return $manager;
        });
    }

    public function boot(): void
    {
        //
    }
}
