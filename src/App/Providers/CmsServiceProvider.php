<?php

namespace Uzwebline\Linecms\App\Providers;

use Uzwebline\Linecms\App\Containers\FrontPageContainer;
use Uzwebline\Linecms\App\Services\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
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
        app()->bind('settings', function () {
            return new SettingsService();
        });

        app()->singleton('front.page', function () {
            return new FrontPageContainer();
        });
    }
}
