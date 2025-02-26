<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
       
        $exceptions->render(function (AuthenticationException $exception, $request) {
            return response()->json([
                'message' => 'Token expired or invalid',
                'status' => 401,
            ], 401);
        });

      
        $exceptions->render(function (\Throwable $exception, $request) {
            return response()->json([
                'error' => 'Something went wrong',
                'status' => 500,
                'message' => $exception->getMessage(),
            ], 500);
        });
    })->create();
    // $app->routeMiddleware([
    //     'admin' => \App\Http\Middleware\AdminMiddleware::class,
    // ]);
    

    