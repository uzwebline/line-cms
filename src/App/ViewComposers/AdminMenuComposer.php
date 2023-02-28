<?php

namespace Uzwebline\Linecms\App\ViewComposers;

use Uzwebline\Linecms\App\Services\ArticleService;
use Illuminate\View\View;

class AdminMenuComposer
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function compose(View $view)
    {
        $article_types = $this->articleService->getArticleTypes(500, 'page', 'post');
        $view->with('article_types', $article_types);
        $article_items = $this->articleService->getArticleTypes(500, 'item');
        $view->with('article_items', $article_items);
    }
}
