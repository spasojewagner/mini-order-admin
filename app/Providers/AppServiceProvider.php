<?php

namespace App\Providers;

use App\Contracts\MessageSender;
use App\Services\Senders\LogSender;
use App\Services\Senders\WhatsAppSender;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
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

        // Amounts in the panel: Serbian dinars, Serbian number format (42.800 RSD).
        Table::configureUsing(fn (Table $table) => $table->defaultCurrency('RSD')->defaultNumberLocale('sr_Latn'));
        Schema::configureUsing(fn (Schema $schema) => $schema->defaultCurrency('RSD')->defaultNumberLocale('sr_Latn'));

        if ($to = config('mail.always_to')) {
            Mail::alwaysTo($to);
        }
    }
}
