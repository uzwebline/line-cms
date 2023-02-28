<?php

namespace Uzwebline\Linecms\App\ViewComposers;

use App\Services\ArticleService;
use Illuminate\View\View;

class AdminArticleComposer
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function compose(View $view)
    {
        $view->with('slug', request('slug'));
    }
}
