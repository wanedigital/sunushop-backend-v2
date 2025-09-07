<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Evaluation;
use App\Observers\EvaluationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Evaluation::observe(EvaluationObserver::class);
    }
}
