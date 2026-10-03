<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.api' => \App\Http\Middleware\AuthenticateApiToken::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => env('VERCEL') || $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $e, Request $request): ?JsonResponse {
            if (! env('VERCEL')) {
                return null;
            }

            report($e);

            if ($e instanceof HttpExceptionInterface) {
                return new JsonResponse([
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : JsonResponse::$statusTexts[$e->getStatusCode()],
                    'status' => $e->getStatusCode(),
                ], $e->getStatusCode(), $e->getHeaders());
            }

            return new JsonResponse([
                'message' => 'Internal Server Error',
                'status' => 500,
            ], 500);
        });
    })->create();
