<?php

namespace Uzwebline\Linecms\App\ViewModels\Member;

use Uzwebline\Linecms\App\Entities\Role;
use Uzwebline\Linecms\App\Entities\User;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Uzwebline\Linecms\App\ViewModels\User\RoleViewModel;
use Carbon\Carbon;

class MemberApiViewModel extends BaseViewModel
{
    public $id;
    public $username;
    public $full_name;
    public $f_name;
    public $l_name;
    public $phone;
    public $status;
    public $status_name;
    public $created_at;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getRolesList'];

    public function __construct(User $user = null)
    {
        if (!is_null($user)) {
            $this->id = $user->id;
            $this->username = $user->username;
            $this->full_name = $user->f_name . ' ' . $user->l_name;
            $this->f_name = $user->f_name;
            $this->l_name = $user->l_name;
            $this->phone = $user->phone;
            $this->status = $user->status;
            $this->status_name = $this->status === true ? trans('all.status_active') : trans('all.status_inactive');
            $this->created_at = Carbon::parse($user->created_at)->format('d.m.Y H:i');
        }
    }
}
