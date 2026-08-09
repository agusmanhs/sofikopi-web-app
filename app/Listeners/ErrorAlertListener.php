<?php

namespace App\Listeners;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Sends a Telegram alert for unhandled exceptions in non-local environments.
 *
 * Wired up in bootstrap/app.php via $exceptions->reportable(). Sentry (also
 * registered in bootstrap/app.php through Sentry's own Integration::handles())
 * remains the source of truth for full stack traces / dedup / history — this
 * listener is only the "you'll actually notice it" push notification layer.
 */
class ErrorAlertListener
{
    public function __construct(
        protected TelegramService $telegramService
    ) {}

    public function handle(Throwable $e): void
    {
        if (app()->environment('local')) {
            return;
        }

        // Cache::add is atomic — two concurrent requests hitting the same
        // exception can't both win the race and double-send the alert.
        $key = 'error-alert:'.md5(get_class($e).$e->getFile().$e->getLine());
        if (! Cache::add($key, true, now()->addMinutes(10))) {
            return;
        }

        $this->telegramService->notify(
            'ERROR PRODUKSI',
            [
                'Exception' => get_class($e),
                'Pesan' => $e->getMessage() ?: '-',
                'Lokasi' => basename($e->getFile()).':'.$e->getLine(),
                'URL' => request()?->fullUrl() ?? '-',
            ],
            '🔥',
            chatId: config('services.telegram.error_chat_id')
        );
    }
}
