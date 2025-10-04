<?php

use App\Http\Middleware\JwtAdminMiddleware;
use App\Http\Middleware\JwtDeviceMiddleware;
use App\Http\Middleware\JwtUserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: 'up',
        apiPrefix: "",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.admin' => JwtAdminMiddleware::class,
            'jwt.user' => JwtUserMiddleware::class,
            'jwt.device' => JwtDeviceMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
