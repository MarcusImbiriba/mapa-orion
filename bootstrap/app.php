<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Putyourlightson\Datastar\Http\Middleware\RegisterScript;
use Putyourlightson\Datastar\Services\Sse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [RegisterScript::class]);
        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(fn (): string => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?StreamedResponse {
            if ($request->header('Datastar-Request') === 'true') {
                return (new Sse)->location(route('login', absolute: false))->getEventStream();
            }

            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request): ?StreamedResponse {
            if ($exception->getStatusCode() === 419 && $request->header('Datastar-Request') === 'true') {
                return (new Sse)->location(route('login', absolute: false))->getEventStream();
            }

            return null;
        });
    })->create();
