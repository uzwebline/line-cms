<?php

namespace Uzwebline\Linecms\App\Providers;

use Uzwebline\Linecms\App\ViewComponents\CatalogFilter;
use Uzwebline\Linecms\App\ViewComposers\FrontComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class WebServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(
            'theme::*', FrontComposer::class
        );

        Blade::component('catalog-filter', CatalogFilter::class);
    }
}
