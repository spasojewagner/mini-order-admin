<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestMail extends Command
{
    protected $signature = 'app:mail-test {to? : Recipient (default: MAIL_FROM_ADDRESS)}';

    protected $description = 'Send one test email to check the mail settings (e.g. Gmail SMTP)';

    public function handle(): int
    {
        $to = $this->argument('to') ?: config('mail.from.address');
        $mailer = config('mail.default');

        $this->line("Mailer: {$mailer}".($mailer === 'smtp' ? ' ('.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').')' : ''));

        try {
            Mail::raw('If you can read this, '.config('app.name').' can send email.', function ($message) use ($to) {
                $message->to($to)->subject(config('app.name').': test email');
            });
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());
            $this->line('For Gmail use an app password (Google account → Security → App passwords), not your normal password.');

            return self::FAILURE;
        }

        $this->info("Sent to {$to}.".($mailer === 'log' ? ' MAIL_MAILER is "log", so it went to storage/logs/laravel.log instead.' : ''));

        return self::SUCCESS;
    }
}
