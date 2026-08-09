<?php

use App\Helpers\ResponseHelper;
use App\Http\Middleware\CheckPegawaiStatus;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureMitraUser;
use App\Http\Middleware\ResolveMitraScope;
use App\Listeners\ErrorAlertListener;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/mitra.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.permission' => CheckPermission::class,
            'check.pegawai.status' => CheckPegawaiStatus::class,
            'mitra.user' => EnsureMitraUser::class,
            'mitra.scope' => ResolveMitraScope::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry: full stack trace, dedup, dan histori — sumber kebenaran untuk debugging.
        Integration::handles($exceptions);

        // Telegram: alert instan yang pasti dilihat (throttled per-fingerprint di listener).
        $exceptions->reportable(function (Throwable $e) {
            app(ErrorAlertListener::class)->handle($e);
        });

        $exceptions->render(function (Throwable $e, $request) {
            // Jika request meminta JSON (API), berikan response JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                Log::error($e);

                $code = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                return ResponseHelper::error(
                    $e->getMessage() ?: 'Internal Server Error',
                    code: $code
                );
            }

            // Untuk Web, biarkan Laravel menangani AuthenticationException agar bisa redirect ke login
            if ($e instanceof AuthenticationException) {
                return null; // Biarkan default handling (redirect ke /login)
            }

            // Handle CSRF Token Mismatch (Error 419)
            if ($e instanceof TokenMismatchException) {
                return redirect()->back()->withInput()->with('error', 'Sesi login telah habis atau token tidak valid. Silakan coba lagi.');
            }
        });
    })->create();
