<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnforceAIUsageLimit;
use App\Http\Middleware\EnforceSubscriptionLimit;
use App\Http\Middleware\EnsureMemberToken;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\VerifyPaymentWebhookSignature;
use App\Http\Responses\ApiResponse;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\Payment\Exceptions\PaymentGatewayException;
use App\Services\Payment\Exceptions\RefundNotAllowedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->throttleApi();

        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'tenant' => ResolveTenant::class,
            'subscription.limit' => EnforceSubscriptionLimit::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'member.guard' => EnsureMemberToken::class,
            'ai.limit' => EnforceAIUsageLimit::class,
            'payment.webhook' => VerifyPaymentWebhookSignature::class,
        ]);

        // ResolveTenant MUST run before SubstituteBindings, or a route like
        // GET /members/{member} resolves the {member} route-model binding
        // (and can 404/leak data cross-tenant) before the tenant scope is
        // even in place. SubstituteBindings is part of the global "api"
        // middleware group, so — unlike auth:sanctum, which Laravel's
        // default priority list already guarantees runs before it — a
        // plain route-level middleware() call on 'tenant' would otherwise
        // run too late. Pin it explicitly, right after auth resolves.
        $middleware->appendToPriorityList(
            after: AuthenticatesRequests::class,
            append: ResolveTenant::class,
        );

        // This is a JSON-only API — there is no "login" web route to redirect
        // guests to, so unauthenticated requests always fall through to the
        // AuthenticationException handler below instead of redirecting.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), $e->errors(), 422);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Resource not found.', [], 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthenticated.', [], 401);
            }
        });

        $exceptions->render(function (AIProviderException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('The AI assistant is temporarily unavailable. Please try again shortly.', [], 502);
            }
        });

        $exceptions->render(function (PaymentGatewayException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), [], 502);
            }
        });

        $exceptions->render(function (RefundNotAllowedException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), [], 422);
            }
        });

        // Catch-all (Phase 30 §5/§2): every exception type ABOVE is
        // registered first and already returns its own safe, intended
        // message, so this only ever runs for something unexpected (a
        // DB error, a null pointer, a third-party SDK throwing its own
        // exception type, etc.). Symfony's HttpExceptionInterface still
        // covers intentional control-flow exceptions (abort(403),
        // 404 route-not-found, 429 throttle, 405 method-not-allowed) —
        // those keep their real status and message since neither is
        // sensitive. Anything else is a genuine bug: log the real
        // exception server-side and never let its message (which can
        // contain SQL, file paths, or other internals) reach the
        // response, regardless of APP_DEBUG.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: match (true) {
                    $status === 404 => 'The requested resource could not be found.',
                    $status === 405 => 'This method is not allowed for this endpoint.',
                    $status === 429 => 'Too many requests. Please slow down and try again shortly.',
                    default => 'Request failed.',
                };

                return ApiResponse::error($message, [], $status);
            }

            report($e);

            return ApiResponse::error('Something went wrong on our end. Please try again shortly.', [], 500);
        });
    })->create();
