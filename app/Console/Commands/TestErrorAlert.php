<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use RuntimeException;

/**
 * Fires a real test exception through Laravel's exception-reporting pipeline
 * (the same path used in production) so both alert channels registered in
 * bootstrap/app.php can be verified end-to-end in one shot:
 *
 *   - Sentry, via \Sentry\Laravel\Integration::handles($exceptions)
 *   - Telegram, via ErrorAlertListener::handle()
 *
 * Unlike the SDK's own `sentry:test` command, this goes through the app's
 * actual reportable() callbacks instead of talking to Sentry directly, so it
 * also exercises ErrorAlertListener's throttle/env-skip logic.
 */
class TestErrorAlert extends Command
{
    protected $signature = 'error-alert:test';

    protected $description = 'Send a test exception through the Sentry + Telegram error-alert pipeline';

    public function handle(ExceptionHandler $handler): int
    {
        $this->info('Current environment: '.app()->environment());

        if (app()->environment('local')) {
            $this->warn('ErrorAlertListener skips Telegram alerts in the "local" environment — Sentry will still receive this event.');
        }

        $exception = new RuntimeException(
            'Test error alert from `error-alert:test` at '.now()->toDateTimeString()
        );

        $this->info('Reporting test exception through app(ExceptionHandler)->report()...');

        $handler->report($exception);

        $this->info('Done. Check Sentry dashboard and the Telegram error chat for the alert.');
        $this->comment('Note: Telegram send is queued (QUEUE_CONNECTION='.config('queue.default').') — run `php artisan queue:work --once` if it is not "sync".');

        return self::SUCCESS;
    }
}
