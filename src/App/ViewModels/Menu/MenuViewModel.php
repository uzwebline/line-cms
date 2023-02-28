<?php

namespace Uzwebline\Linecms\App\ViewModels\Menu;

use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Entities\CreditPlan;
use Uzwebline\Linecms\App\Entities\Menu;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class MenuViewModel extends BaseViewModel
{
    public $id;
    public $name;
    public $slug;
    public $locale;
    public $status;
    public $created_at;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getRolesList'];

    public function __construct(Menu $menu = null)
    {
        if (!is_null($menu)) {
            $this->id = $menu->id;
            $this->name = $menu->name;
            $this->slug = $menu->slug;
            $this->locale = $menu->locale;
            $this->status = $menu->status;
            $this->created_at = Carbon::parse($menu->created_at)->format('d.m.Y H:i');
        }
    }

    public function getStatusClass()
    {
        return $this->status === true ? "success" : "danger";
    }

    public function getStatusName()
    {
        return $this->status === true ? trans('all.status_active') : trans('all.status_inactive');
    }
}
