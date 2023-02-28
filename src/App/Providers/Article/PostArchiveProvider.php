<?php

namespace Uzwebline\Linecms\App\Providers\Article;

class PostArchiveProvider implements ArticleProviderContract
{
    public function getTypeName(): string
    {
        return 'post_archive';
    }

    public function creatable(): bool
    {
        return false;
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
        return false;
    }

    public function isElement(): bool
    {
        return false;
    }
}
