<?php

namespace Uzwebline\Linecms\App\ViewModels\Management;

use Uzwebline\Linecms\App\Entities\Geolocation;
use Uzwebline\Linecms\App\Entities\Management;
use Uzwebline\Linecms\App\Entities\Role;
use Uzwebline\Linecms\App\Entities\User;
use Uzwebline\Linecms\App\Services\SettingsService;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Uzwebline\Linecms\App\ViewModels\User\RoleViewModel;
use Carbon\Carbon;

class ManagementViewModel extends BaseViewModel
{
    public $id;
    public $full_name;
    public $image;
    public $position;
    public $description;
    public $acceptance;
    public $phone;
    public $fax;
    public $created_at;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getRolesList'];

    public function __construct(Management $management = null)
    {
        if (!is_null($management)) {
            $this->id = $management->id;
            $this->full_name = $management->full_name;
            $this->image = $management->image;
            $this->position = $management->position;
            $this->description = $management->description;
            $this->phone = $management->phone;
            $this->fax = $management->fax;
            $this->acceptance = $management->acceptance;
            $this->created_at = Carbon::parse($management->created_at)->format('d.m.Y H:i');
        }
    }

    public function getLocalizedPosition(?string $locale = null)
    {
        $locale = app()->getLocale();
        return $this->position[$locale] ?? "";
    }

    public function getLocalizedName(?string $locale = null)
    {
        $locale = app()->getLocale();
        return $this->full_name[$locale] ?? "";
    }

    public function getLocalizedAcceptance(?string $locale = null)
    {
        $locale = app()->getLocale();
        return $this->acceptance[$locale] ?? "";
    }
    public function getLocalizedDescription(?string $locale = null)
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? "";
    }
}
