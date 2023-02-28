<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Support\Fluent;

class LocaleModel
{
    public static function defaultForContent()
    {
        $path = resource_path('collections/locales.json');
        $items = json_decode(file_get_contents($path), true);
        return new Fluent($items['Content']['default']);
    }

    public static function forContent()
    {
        $path = resource_path('collections/locales.json');
        $items = json_decode(file_get_contents($path), true);
        return collect($items['Content']['locales']);
    }

    public static function forAdmin()
    {
        $path = resource_path('collections/locales.json');
        $items = json_decode(file_get_contents($path), true);
        return collect($items['Admin']['locales']);
    }
}
