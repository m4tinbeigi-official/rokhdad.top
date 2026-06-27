<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception instanceof AuthenticationException
                ? 401
                : ($exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500);

            if ($exception instanceof ValidationException) {
                $status = $exception->status;
            }

            $payload = [
                'message' => $exception->getMessage() ?: 'Server Error',
                'status' => $status,
                'error' => class_basename($exception),
            ];

            if ($exception instanceof ValidationException) {
                $payload['errors'] = $exception->errors();
            }

            return response()->json($payload, $status);
        });
    })->create();
