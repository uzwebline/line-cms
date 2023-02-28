<?php

namespace Uzwebline\Linecms\App\ViewModels\User;

use Uzwebline\Linecms\App\Entities\Role;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Illuminate\Support\Fluent;

class RoleViewModel extends BaseViewModel
{
    public $id;
    public $name;
    public $display_name;
    public $description;
    public $system;
    public $permissions;

    protected $ignore = ['getGuardsList', 'getPermissionsList'];

    public function __construct(Role $role = null)
    {
        if (!is_null($role)) {
            $this->id = $role->id;
            $this->name = $role->name;
            $this->display_name = $role->display_name;
            $this->description = $role->description;
            $this->permissions = $role->permissions->map(function ($item) {
                return $item->name;
            })->toArray();
            $this->system = $role->system;
        }
    }

    public function getPermissionsList()
    {
        $path = base_path('user_permissions.json');
        $permissions = json_decode(file_get_contents($path), true);
        return collect($permissions);
    }
}
