<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

class HomePageViewModel extends PageViewModel
{
    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        $view                   = "home";
        $this->view             = "theme::$view";
        $this->meta_title       = $this->page_container->getFrontService()->getSetting('site_name');
        $this->meta_description = $this->page_container->getFrontService()->getSetting('site_description');
        $this->meta_keywords    = $this->page_container->getFrontService()->getSetting('site_keywords');

        $this->title = $this->page_container->getFrontService()->getSetting('site_name');
    }
}
