<?php

namespace Uzwebline\Linecms\App\Providers\Article;


interface ArticleProviderContract
{
    public function getTypeName(): string;

    public function creatable(): bool;

    public function mustHaveParentPage(): bool;

    public function canHaveTaxonomy(): bool;

    public function canHaveFields(): bool;

    public function canHaveHierarchy(): bool;

    public function isElement(): bool;
}
