<?php

use App\Http\Middleware\EnsureGuruHasMapelWorkspace;
use App\Http\Middleware\EnsureGuruHasWaliKelasWorkspace;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsGuru;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => match ($request->user()?->role) {
            'admin' => route('admin.dashboard'),
            'guru' => route('guru.dashboard'),
            default => url('/'),
        });

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'guru' => EnsureUserIsGuru::class,
            'guru.mapel' => EnsureGuruHasMapelWorkspace::class,
            'guru.wali' => EnsureGuruHasWaliKelasWorkspace::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
