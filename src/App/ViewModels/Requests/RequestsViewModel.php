<?php

namespace Uzwebline\Linecms\App\ViewModels\Requests;

use Uzwebline\Linecms\App\Entities\Geolocation;
use Uzwebline\Linecms\App\Entities\Management;
use Uzwebline\Linecms\App\Entities\Requests;
use Uzwebline\Linecms\App\Entities\Role;
use Uzwebline\Linecms\App\Entities\User;
use Uzwebline\Linecms\App\Services\SettingsService;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Uzwebline\Linecms\App\ViewModels\User\RoleViewModel;
use Carbon\Carbon;

class RequestsViewModel extends BaseViewModel
{
    public $id;
    public $full_name;
    public $phone;
    public $theme;
    public $body;
    public $created_at;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getRolesList'];

    public function __construct(Requests $requests = null)
    {
        if (!is_null($requests)) {
            $this->id         = $requests->id;
            $this->full_name  = $requests->full_name;
            $this->phone      = $requests->phone;
            $this->email      = $requests->email;
            $this->theme      = $requests->theme;
            $this->body       = $requests->body;
            $this->created_at = Carbon::parse($requests->created_at)->format('d.m.Y H:i');
        }
    }

    public function getLocalizedPosition(?string $locale = null)
    {
        $locale = app()->getLocale();

        return $this->position[$locale] ?? "";
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
