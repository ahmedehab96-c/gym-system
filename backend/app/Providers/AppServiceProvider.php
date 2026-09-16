<?php

namespace App\Providers;

use App\Services\AI\Contracts\AIProviderContract;
use App\Services\AI\Providers\OpenAIProvider;
use App\Services\Communication\Contracts\PushProviderContract;
use App\Services\Communication\Contracts\WhatsAppProviderContract;
use App\Services\Communication\Providers\FcmProvider;
use App\Services\Communication\Providers\WhatsAppCloudApiProvider;
use App\Services\Payment\Contracts\PaymentGatewayContract;
use App\Services\Payment\Providers\StripeProvider;
use App\Support\CurrentTenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class);

        // The only place that picks a concrete AI provider — everything
        // above this (App\Services\AI\AIManager and every feature
        // service) depends on AIProviderContract only. Adding another
        // provider later means adding a match() arm here, nowhere else.
        $this->app->bind(AIProviderContract::class, function () {
            return match (config('ai.default')) {
                default => new OpenAIProvider(config('ai.providers.openai')),
            };
        });

        // Same one-place-only pattern as AIProviderContract above — every
        // payment service depends on PaymentGatewayContract only.
        $this->app->bind(PaymentGatewayContract::class, function () {
            return match (config('payment.default')) {
                default => new StripeProvider(config('payment.gateways.stripe')),
            };
        });

        $this->app->bind(WhatsAppProviderContract::class, function () {
            return match (config('communication.whatsapp_provider')) {
                default => new WhatsAppCloudApiProvider(config('communication.whatsapp.whatsapp_cloud_api')),
            };
        });

        $this->app->bind(PushProviderContract::class, function () {
            return match (config('communication.push_provider')) {
                default => new FcmProvider(config('communication.push.fcm')),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
