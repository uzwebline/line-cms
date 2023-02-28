<?php

namespace Uzwebline\Linecms\App\Services\Admin;


use Uzwebline\Linecms\App\Entities\LocaleModel;

class AdminService
{

    public static function getContentLocales(): array
    {
        return LocaleModel::forContent()->toArray();
    }

    public static function getDefaultContentLocale()
    {
        return LocaleModel::defaultForContent();
    }
}
