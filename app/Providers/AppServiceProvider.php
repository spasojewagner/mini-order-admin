<?php

namespace App\Providers;

use App\Contracts\MessageSender;
use App\Services\Senders\LogSender;
use App\Services\Senders\WhatsAppSender;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MessageSender::class, function () {
            return match (config('services.alerts.driver')) {
                'whatsapp' => new WhatsAppSender,
                default => new LogSender,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
