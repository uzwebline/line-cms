<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

class NotFoundPageViewModel extends PageViewModel
{
    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        $view = "404";
        $this->view = "theme::$view";
        $this->http_status = 404;

        $this->title = trans('theme::all.page_not_found');
        $this->content = trans('theme::all.page_not_found_content');
    }
}
