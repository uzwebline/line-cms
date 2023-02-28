<?php

namespace Uzwebline\Linecms\App\Providers\Article;

use Illuminate\Support\Collection;

class ItemProvider implements ArticleProviderContract
{
    public function getTypeName(): string
    {
        return 'item';
    }

    public function creatable(): bool
    {
        return true;
    }

    public function mustHaveParentPage(): bool
    {
        return false;
    }

    public function canHaveTaxonomy(): bool
    {
        return true;
    }

    public function canHaveFields(): bool
    {
        return true;
    }

    public function canHaveHierarchy(): bool
    {
        return true;
    }

    public function isElement(): bool
    {
        return true;
    }

    public function getTemplatesList(): Collection
    {
        $theme = config('app.theme');
        $config_path = base_path("themes/$theme/config.json");
        $config = json_decode(file_get_contents($config_path), true);
        return collect($config['templates'][$this->getTypeName()] ?? []);
    }
}
