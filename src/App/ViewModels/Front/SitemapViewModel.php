<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

use Uzwebline\Linecms\App\Entities\Management;
use Uzwebline\Linecms\App\ViewModels\Management\ManagementViewModel;

class SitemapViewModel extends PageViewModel
{

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        $view = "sitemap";
        $this->view = "theme::$view";
//        $this->title = trans('theme::all.command_page_title') ?? "";
//        $this->description = trans('theme::all.command_page_description') ?? "";

        $this->title = $this->page_container->getFrontService()->getSetting('site_name');
    }
}
