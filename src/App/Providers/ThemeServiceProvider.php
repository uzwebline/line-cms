<?php

namespace Uzwebline\Linecms\App\Providers;

use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $theme = config('app.theme');

        $views = base_path("themes/$theme");

        $this->loadViewsFrom($views, 'theme');

        $translations = base_path("themes/$theme/lang");

        $this->loadTranslationsFrom($translations, 'theme');
    }
}
