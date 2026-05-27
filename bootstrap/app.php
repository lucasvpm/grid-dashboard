<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sem login: middleware padrão web já cuida de sessão, CSRF e cookies.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tratamentos customizados podem entrar aqui se necessário.
    })
    ->create();
