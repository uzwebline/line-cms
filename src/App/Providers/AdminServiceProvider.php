<?php

namespace Uzwebline\Linecms\App\Providers;

use Uzwebline\Linecms\App\ViewComponents\AdditionalField;
use Uzwebline\Linecms\App\ViewComposers\AdminArticleComposer;
use Uzwebline\Linecms\App\ViewComposers\AdminMenuComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(
            'sidebar.menu', AdminMenuComposer::class
        );
        View::composer(
            'article.*', AdminArticleComposer::class
        );

        Blade::component('additional-field', AdditionalField::class);
    }
}
