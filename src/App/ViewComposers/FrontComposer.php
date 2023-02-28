<?php

namespace Uzwebline\Linecms\App\ViewComposers;

use App\Containers\FrontPageContainer;
use Illuminate\View\View;

class FrontComposer
{
    /**
     * @var FrontPageContainer
     */
    protected $page_container;

    public function __construct()
    {
        $this->page_container = app('front.page');
    }

    public function compose(View $view)
    {
        $view->with('page', $this->page_container->getPage());
        $view->with('util', $this->page_container->getFrontService());
    }
}
