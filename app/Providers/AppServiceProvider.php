<?php

namespace App\Providers;

use App\Support\Breadcrumb;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Inject breadcrumb-ul calculat din ruta curenta in topbar.
        // View composer-ul ruleaza la fiecare randare de layouts.topbar.
        View::composer('layouts.topbar', function ($view) {
            $view->with('breadcrumbData', Breadcrumb::current());
        });
    }
}
