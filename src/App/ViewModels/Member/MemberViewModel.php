<?php

namespace Uzwebline\Linecms\App\ViewModels\Member;

use Uzwebline\Linecms\App\Entities\Role;
use Uzwebline\Linecms\App\Entities\User;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Uzwebline\Linecms\App\ViewModels\User\RoleViewModel;
use Carbon\Carbon;

class MemberViewModel extends BaseViewModel
{
    public $id;
    public $username;
    public $full_name;
    public $f_name;
    public $l_name;
    public $phone;
    public $roles;
    public $role_names;
    public $status;
    public $created_at;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getRolesList'];

    public function __construct(User $user = null)
    {
        if (!is_null($user)) {
            $this->id = $user->id;
            $this->username = $user->username;
            $this->full_name = $user->f_name.' '.$user->l_name;
            $this->f_name = $user->f_name;
            $this->l_name = $user->l_name;
            $this->phone = $user->phone;
            $this->roles = $user->roles->map(function ($item) {
                return new RoleViewModel($item);
            });
            $this->role_names = $user->roles->map(function ($item) {
                return $item->display_name;
            })->join(',');
            $this->status = $user->status;
            $this->created_at = Carbon::parse($user->created_at)->format('d.m.Y H:i');
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

    public function getRolesList()
    {
        return Role::get()->map(function ($item) {
            return new RoleViewModel($item);
        });
    }
}
