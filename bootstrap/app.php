<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 401 Unauthorized
        $exceptions->render(function (AuthenticationException $exception, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        });

        // 403 Forbidden
        $exceptions->render(function (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
        });

        // 404 Not Found
        $exceptions->render(function (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        });

        // 422 Unprocessable Entity
        $exceptions->render(function (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors()
            ], 422);
        });

        // 429 Too Many Requests
        $exceptions->render(function (ThrottleRequestsException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests, please slow down'
            ], 429);
        });

        // 4xx or 5xx Http Error
        $exceptions->render(function (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Http Error'
            ], $exception->getStatusCode());
        });

        // 500 Internal Server Error (Generic Exception Handler)
        $exceptions->render(function (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        });
    })->create();
