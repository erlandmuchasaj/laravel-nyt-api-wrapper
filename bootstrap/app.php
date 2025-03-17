<?php

use App\Http\Middleware\AddXHeader;
use App\Http\Middleware\IdempotencyMiddleware;
use ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(AddXHeader::class);

        $middleware->alias([
            'idempotent' => IdempotencyMiddleware::class,
            'gzip' => GzipEncodeResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Truncate request exception messages to 240 characters...
        $exceptions->dontTruncateRequestExceptions();
    })->create();
