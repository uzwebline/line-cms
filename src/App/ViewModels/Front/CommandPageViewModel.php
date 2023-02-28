<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

use Uzwebline\Linecms\App\Entities\Management;
use Uzwebline\Linecms\App\ViewModels\Management\ManagementViewModel;

class CommandPageViewModel extends PageViewModel
{
    public $items;
    public $banner_title;
    public $banner_description;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        $view = "team.team";
        $this->view = "theme::$view";
        $items = Management::query()->get();
        $this->items = $items->transform(function ($item) {
            return new ManagementViewModel($item);
        });
        $this->title = trans('theme::all.command_page_title') ?? "";
        $this->description = trans('theme::all.command_page_description') ?? "";

//        $this->title = $this->page_container->getFrontService()->getSetting('site_name');
    }
}
