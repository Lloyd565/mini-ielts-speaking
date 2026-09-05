<?php

namespace App\Providers;

use App\Services\Contracts\EvaluationServiceInterface;
use App\Services\FakeEvaluationService;
use App\Services\GeminiEvaluationService;
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
        $this->app->bind(EvaluationServiceInterface::class, function () {
            // No key locally? Serve canned feedback instead of failing every submit.
            return config('services.gemini.key')
                ? new GeminiEvaluationService
                : new FakeEvaluationService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tight throttle on answer submissions to avoid abusive/expensive Gemini calls.
        RateLimiter::for('speaking-submit', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
