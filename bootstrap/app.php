<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                $status = 500;
                if ($e instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                } elseif ($e instanceof Illuminate\Validation\ValidationException) {
                    $status = 422;
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ], $status);
                } elseif ($e instanceof Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                    return response()->json([
                        'message' => 'Unauthenticated',
                    ], $status);
                }

                return response()->json([
                    'message' => $e->getMessage() ?: 'Server Error',
                ], $status);
            }
        });
    })->create();
