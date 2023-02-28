<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\LaratrustUserTrait;

class User extends Authenticatable
{
    use Notifiable, LaratrustUserTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'f_name', 'l_name', 'phone', 'email', 'password', 'status','synced'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'status' => "boolean"
    ];

    public function getFullNameAttribute()
    {
        return $this->f_name . ' ' . $this->l_name;
    }

    public function scopeAdmins($query)
    {
        $roles = Role::where('system', 0)->orWhere('name', 'admin')->get()->map(function ($item) {
            return $item->name;
        })->toArray();

        return $query->whereRoleIs($roles);
    }

    public function scopeMembers($query)
    {
        return $query->whereRoleIs(['member']);
    }

    public function scopeTelegramMembers($query)
    {
        return $query->whereRoleIs(['telegram_member']);
    }

    public function scopeAllMembers($query)
    {
        return $query->whereRoleIs(['telegram_member','member']);
    }

    public function scopeSellers($query)
    {
        return $query->whereRoleIs(['seller']);
    }

    public function data()
    {
        return $this->hasOne(UserData::class);
    }

    public function meta()
    {
        return $this->hasMany(UserMeta::class);
    }
}
