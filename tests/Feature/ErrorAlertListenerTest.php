<?php

namespace Tests\Feature;

use App\Listeners\ErrorAlertListener;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Coverage for the production error-alert path wired in bootstrap/app.php:
 * ErrorAlertListener::handle() should notify Telegram exactly once per
 * distinct exception fingerprint within the throttle window, and stay
 * silent entirely in the local environment.
 */
class ErrorAlertListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_sends_telegram_alert_for_unhandled_exception(): void
    {
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notify')
            ->once()
            ->with(
                'ERROR PRODUKSI',
                Mockery::on(function (array $details) {
                    return $details['Exception'] === RuntimeException::class
                        && $details['Pesan'] === 'Something broke';
                }),
                '🔥',
                null,
                Mockery::on(fn ($chatId) => true)
            );

        $listener = new ErrorAlertListener($telegram);
        $listener->handle(new RuntimeException('Something broke'));
    }

    public function test_throttles_repeated_identical_exceptions(): void
    {
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notify')->once();

        $listener = new ErrorAlertListener($telegram);
        $exception = new RuntimeException('Repeated error');

        // Same class + file + line twice in a row within the throttle window
        // must only alert once.
        $listener->handle($exception);
        $listener->handle($exception);
    }

    public function test_does_not_throttle_distinct_exceptions(): void
    {
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notify')->twice();

        $listener = new ErrorAlertListener($telegram);
        $listener->handle(new RuntimeException('First error'));
        $listener->handle(new \LogicException('Second error'));
    }

    public function test_skips_alert_in_local_environment(): void
    {
        app()['env'] = 'local';

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldNotReceive('notify');

        $listener = new ErrorAlertListener($telegram);
        $listener->handle(new RuntimeException('Local error, should not alert'));

        app()['env'] = 'testing';
    }
}
