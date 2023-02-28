<?php

namespace Uzwebline\Linecms\App\Providers\Article;

use Illuminate\Support\Collection;

class PageProvider implements ArticleProviderContract
{
    public function getTypeName(): string
    {
        return 'page';
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
        return false;
    }

    public function canHaveFields(): bool
    {
        return false;
    }

    public function canHaveHierarchy(): bool
    {
        return true;
    }

    public function isElement(): bool
    {
        return false;
    }

    /**
     * @return Collection
     */
    public function getTemplatesList(): Collection
    {
        $theme = config('app.theme');
        $config_path = base_path("themes/$theme/config.json");
        $config = json_decode(file_get_contents($config_path), true);
        return collect($config['templates'][$this->getTypeName()] ?? []);
    }
}
