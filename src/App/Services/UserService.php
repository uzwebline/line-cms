<?php

namespace Uzwebline\Linecms\App\Services;

use Uzwebline\Linecms\App\Entities\Permission;
use Uzwebline\Linecms\App\Entities\Role;
use Uzwebline\Linecms\App\Entities\User;
use Uzwebline\Linecms\App\Entities\UserData;
use Uzwebline\Linecms\App\Exceptions\OperationException;
use Uzwebline\Linecms\App\Notifications\RegisterMember;
use Uzwebline\Linecms\App\Notifications\RestoreAccess;
use Uzwebline\Linecms\App\Requests\Api\ApiRestoreNewPasswordRequest;
use Uzwebline\Linecms\App\Requests\Front\ChangeProfileRequest;
use Uzwebline\Linecms\App\Requests\Front\NewPasswordRequest;
use Uzwebline\Linecms\App\Requests\Front\PreRegisterRequest;
use Uzwebline\Linecms\App\Requests\Front\RegisterRequest;
use Uzwebline\Linecms\App\Requests\Front\RestoreRequest;
use Uzwebline\Linecms\App\Requests\Member\CreateMemberRequest;
use Uzwebline\Linecms\App\Requests\Member\UpdateMemberRequest;
use Uzwebline\Linecms\App\Requests\User\CreateRoleRequest;
use Uzwebline\Linecms\App\Requests\User\CreateUserRequest;
use Uzwebline\Linecms\App\Requests\User\UpdateRoleRequest;
use Uzwebline\Linecms\App\Requests\User\UpdateUserRequest;
use Uzwebline\Linecms\App\Structures\Phone;
use Uzwebline\Linecms\App\TransferObjects\Front\PreRegisterFrontMemberResult;
use Uzwebline\Linecms\App\TransferObjects\Front\RegisterFrontMemberResult;
use Uzwebline\Linecms\App\TransferObjects\Front\RestoreFrontMemberAccessResult;
use Uzwebline\Linecms\App\TransferObjects\ResultBase;
use Uzwebline\Linecms\App\TransferObjects\User\RestoreAccessResult;
use Uzwebline\Linecms\App\ViewModels\Member\MemberViewModel;
use Uzwebline\Linecms\App\ViewModels\User\RoleViewModel;
use Uzwebline\Linecms\App\ViewModels\User\UserViewModel;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Ramsey\Uuid\Uuid;

class UserService
{
    #region Roles

    public function paginateRoles($limit = 25): LengthAwarePaginator
    {
        $pagination = Role::paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new RoleViewModel($value);
        });

        return $pagination;
    }

    public function getRole(int $role_id): RoleViewModel
    {
        return new RoleViewModel(Role::find($role_id));
    }

    public function createRole(CreateRoleRequest $request): RoleViewModel
    {
        $data        = $request->validated();
        $create_role = collect($data)->only(['display_name', 'description'])->toArray();

        $permissions = $data['permissions'] ?? [];

        $existing_permissions = Permission::whereIn('name', $permissions)
            ->get('name')->transform(function ($item) {
                return $item->name;
            })->toArray();

        foreach ($permissions as $permission) {
            if (!in_array($permission, $existing_permissions)) {
                Permission::create([
                                       'name' => $permission,
                                   ]);
            }
        }

        $create_role['name'] = $data['slug'];

        $role = Role::create($create_role);
        $role->syncPermissions($permissions);

        return new RoleViewModel($role);
    }

    public function updateRole(int $role_id, UpdateRoleRequest $request)
    {
        $data = $request->validated();

        $permissions = $data['permissions'] ?? [];

        $existing_permissions = Permission::whereIn('name', $permissions)
            ->get('name')->transform(function ($item) {
                return $item->name;
            })->toArray();

        foreach ($permissions as $permission) {
            if (!in_array($permission, $existing_permissions)) {
                Permission::create([
                                       'name' => $permission,
                                   ]);
            }
        }

        $role               = Role::find($role_id);
        $role->display_name = $data['display_name'];
        //$role->name = $data['name'];
        $role->description = $data['description'];
        $role->save();

        $role->syncPermissions($permissions);
    }

    public function deleteRole(int $role_id)
    {
        $role = Role::find($role_id);
        $role->delete();
    }

    #endregion Roles

    #region Users

    public function paginateUsers($limit = 25): LengthAwarePaginator
    {
        $pagination = User::admins()->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new UserViewModel($value);
        });

        return $pagination;
    }

    public function getUser(int $id): UserViewModel
    {
        $user = User::admins()->find($id);
        if (is_null($user)) {
            throw new OperationException("User not found");
        }

        return new UserViewModel($user);
    }

    public function createUser(CreateUserRequest $request): UserViewModel
    {
        $user = User::create(array_merge($request->validated()));
        $user->syncRoles([$request->validated()['role']]);

        return new UserViewModel($user);
    }

    public function updateUser(int $id, UpdateUserRequest $request): bool
    {
        $data = $request->validated();

        if (!isset($data['status'])) {
            $data['status'] = false;
        }

        $update_data = collect($data)->only(['username', 'f_name', 'l_name', 'phone', 'status'])->toArray();

        if (isset($data['password'])) {
            $update_data['password'] = bcrypt($data['password']);
        }

        $user = User::admins()->find($id);

        if (is_null($user)) {
            throw new OperationException("User not found");
        }

        $user->syncRoles([$data['role']]);

        return $user->update($update_data);
    }

    public function deleteUser(int $id)
    {
        $user = User::admins()->find($id);

        if (is_null($user)) {
            throw new OperationException("User not found");
        }

        return $user->delete();
    }

    #endregion Roles

    #region Members

    public function paginateMembers($limit = 25): LengthAwarePaginator
    {
        $pagination = User::members()->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new MemberViewModel($value);
        });

        return $pagination;
    }

    public function paginateAllMembers($limit = 25): LengthAwarePaginator
    {
        $pagination = User::allMembers()
            ->orderBy('users.created_at', 'DESC')
            ->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new MemberViewModel($value);
        });

        return $pagination;
    }

    public function getMember(int $id): MemberViewModel
    {
        $user = User::members()->find($id);
        if (is_null($user)) {
            throw new OperationException("User not found");
        }

        return new MemberViewModel($user);
    }

    public function createMember(CreateMemberRequest $request): MemberViewModel
    {
        $data                    = $request->validated();
        $create_user             = collect($data)
            ->only(['username', 'f_name', 'l_name', 'phone', 'password', 'status'])
            ->toArray();
        $create_user['password'] = bcrypt($create_user['password']);
        $user                    = User::create($create_user);
        $user->syncRoles([$data['role']]);

        return new MemberViewModel($user);
    }

    public function updateMember(int $id, UpdateMemberRequest $request): bool
    {
        $data = $request->validated();

        if (!isset($data['status'])) {
            $data['status'] = false;
        }

        $update_data = collect($data)->only(['username', 'f_name', 'l_name', 'phone', 'status'])->toArray();

        if (isset($data['password'])) {
            $update_data['password'] = bcrypt($data['password']);
        }

        $user = User::members()->find($id);

        if (is_null($user)) {
            throw new OperationException("User not found");
        }

        $user->syncRoles([$data['role']]);

        return $user->update($update_data);
    }

    public function deleteMember(int $id)
    {
        $user = User::members()->find($id);

        if (is_null($user)) {
            throw new OperationException("User not found");
        }

        return $user->delete();
    }

    /**
     * @param \Laravel\Socialite\Contracts\User $social_user
     * @param string $provider
     *
     * @return MemberViewModel|null
     */
    public function createOrUpdateMemberFromSocial(
        \Laravel\Socialite\Contracts\User $social_user,
        string $provider
    ): ?MemberViewModel {
        $username = $provider . '_' . $social_user->getId();

        $role = Role::query()->firstWhere('name', 'member');

        if (is_null($role)) {
            return null;
        }

        $user = User::query()->members()->firstWhere("username", $username);

        if (is_null($user)) {
            $create_user             = [
                'username' => $username,
                'f_name'   => $social_user->getName(),
                'l_name'   => '',
                'phone'    => '',
                'email'    => $social_user->getEmail(),
                'status'   => true,
            ];
            $create_user['password'] = bcrypt(Uuid::uuid4()->getHex()->toString());
            $user                    = User::create($create_user);
            $user->syncRoles([$role->id]);
        } else {
            $update_data = [
                'f_name' => $social_user->getName(),
                'l_name' => '',
                'phone'  => '',
                'email'  => $social_user->getEmail(),
                'status' => true,
            ];
            $user->update($update_data);
            $user->syncRoles([$role->id]);
        }

        return new MemberViewModel($user);
    }

    #endregion Members

    public function restoreAccess(int $user_id)
    {
        $user = User::find($user_id);

        if (is_null($user)) {
            throw new OperationException(trans('exception.member_not_found'));
        }

        $otp = NotificationService::createOtp($user->phone, "user.restore", $user_id);

        Notification::route('sms', $user->phone)
            ->notify(new RestoreAccess($otp->pin));

        return new RestoreAccessResult([
                                           'token' => $otp->ref_id,
                                           'phone' => $user->phone,
                                           'ttl'   => $otp->ttl,
                                       ]);
    }

    public function restoreNewPassword(ApiRestoreNewPasswordRequest $request)
    {
        $data = $request->validated();

        $checkResult = NotificationService::checkOtp($data['token'], $data['pin'], "user.restore");

        if (!$checkResult->success) {
            return (new ResultBase())->setError($checkResult->error);
        }

        $user = User::find($checkResult->value);

        if (is_null($user) || $checkResult->key !== $user->phone) {
            throw new OperationException(trans('exception.member_not_found'));
        }

        $user->password = bcrypt($data['password']);
        $user->save();

        return new ResultBase();
    }

    public function preRegisterFrontMember(PreRegisterRequest $request): PreRegisterFrontMemberResult
    {
        $data = $request->validated();

        $birth_date = Carbon::createFromFormat('d.m.Y', $data['birth_date']);

        if ($birth_date->age > 150) {
            return (new PreRegisterFrontMemberResult)->setError(trans('errors.invalid_birth_date'));
        }

        if ($birth_date->age < 18) {
            return (new PreRegisterFrontMemberResult)->setError(trans('errors.18_plus_you_can_not_register'));
        }

        $phone = Phone::parseFull($data['phone']);

        $user_exists = User::members()->where('username', '=', $phone)->exists();

        if ($user_exists) {
            return (new PreRegisterFrontMemberResult)->setError(trans('errors.username_already_in_use'));
        }

        $otp = NotificationService::createOtp($phone, "member.register", $phone, 10, 5);

        Notification::route('sms', $phone)
            ->notify(new RegisterMember($otp->pin));

        return new PreRegisterFrontMemberResult([
                                                    'token' => $otp->ref_id,
                                                ]);
    }

    public function registerFrontMember(RegisterRequest $request): RegisterFrontMemberResult
    {
        $data = $request->validated();

        $checkResult = NotificationService::checkOtp($data['token'], $data['pin'], "member.register");

        if (!$checkResult->success) {
            return (new RegisterFrontMemberResult())->setError($checkResult->error);
        }

        $phone = Phone::parseFull($data['phone']);

        DB::beginTransaction();

        $user = User::members()
            ->where('username', '=', $phone)
            ->lockForUpdate()
            ->first();

        if (is_null($user)) {
            DB::rollBack();
            $create_user = [
                'username' => $phone,
                'f_name'   => $data['f_name'],
                'l_name'   => $data['l_name'],
                'phone'    => Phone::parseFull($data['phone']),
                'password' => bcrypt($data['password']),
                'status'   => 1,
            ];

            $user = User::query()->create($create_user);

            UserData::query()->create([
                                          'user_id'    => $user->id,
                                          'birth_date' => Carbon::createFromFormat('d.m.Y', $data['birth_date']),
                                          'sex'        => $data['sex'],
                                          'region_id'  => $data['region'],
                                      ]);

            $user->syncRoles([2]);// member;

            $this->syncFrontMember($user->id);

            auth()->loginUsingId($user->id);

            return new RegisterFrontMemberResult([
                                                     'redirect' => route('front.cabinet'),
                                                 ]);
        } else {
            DB::rollBack();
            auth()->loginUsingId($user->id);

            return new RegisterFrontMemberResult([
                                                     'redirect' => route('front.cabinet'),
                                                 ]);
        }
    }

    public function restoreFrontMemberAccess(RestoreRequest $request): RestoreFrontMemberAccessResult
    {
        $data = $request->validated();

        $phone = Phone::parseFull($data['phone']);

        $user = User::members()
            ->where('username', '=', $phone)
            ->where('status', '=', 1)
            ->first();

        if (is_null($user)) {
            return (new RestoreFrontMemberAccessResult)->setError(trans('errors.restore_phone_not_found'));
        }

        $otp = NotificationService::createOtp($user->phone, "member.restore", $user->id);

        Notification::route('sms', $user->phone)
            ->notify(new RestoreAccess($otp->pin));

        return new RestoreFrontMemberAccessResult([
                                                      'token' => $otp->ref_id,
                                                  ]);
    }

    public function restoreFrontMemberNewPassword(NewPasswordRequest $request)
    {
        $data = $request->validated();

        $checkResult = NotificationService::checkOtp($data['token'], $data['pin'], "member.restore");

        if (!$checkResult->success) {
            return (new ResultBase())->setError($checkResult->error);
        }

        $user = User::members()->find($checkResult->value);

        if (is_null($user) || $checkResult->key !== $user->phone) {
            return (new RestoreFrontMemberAccessResult)->setError(trans('errors.restore_phone_not_found'));
        }

        $user->password = bcrypt($data['password']);
        $user->save();

        return new ResultBase();
    }

    public function changeFrontMemberProfile(int $user_id, ChangeProfileRequest $request)
    {
        $data = $request->validated();

        $user = User::query()->find($user_id);

        if (!is_null($user)) {
            $user_data = UserData::query()->where('user_id', '=', $user_id)->first();

            if (empty($data['password'])) {
                $user->update([
                                  'f_name' => $data['f_name'],
                                  'l_name' => $data['l_name'],
                              ]);
            } else {
                $user->update([
                                  'f_name'   => $data['f_name'],
                                  'l_name'   => $data['l_name'],
                                  'password' => bcrypt($data['password']),
                              ]);
            }

            if (!is_null($user_data)) {
                $user_data->update([
                                       'birth_date' => Carbon::createFromFormat('d.m.Y', $data['birth_date']),
                                       'sex'        => $data['sex'],
                                       'region_id'  => $data['region'],
                                   ]);
            }

            $this->syncFrontMember($user_id);
        }

        return new ResultBase();
    }

    public function syncFrontMember(int $user_id)
    {
        $user = User::query()->find($user_id);

        if (!is_null($user)) {
            $user_data = UserData::query()->where('user_id', '=', $user_id)->first();

            $source = 0; // 0 - sms, 1 - web, 2 - telegram

            if ($user->hasRole('member')) {
                $source = 1;
            } elseif ($user->hasRole('telegram_member')) {
                $source = 2;
            }

            if ($source > 0 && !is_null($user_data)) {
                $client   = new Client();
                $url      = 'http://service.flashup-promo.uz/api/data/store-member';
                $username = 'flashup_promo_api_user';
                $password = 'q]5=\P8M~Y9k"t<j]nj)!g36YT</sC?]';

                try {
                    $response = $client->post($url, [
                        'auth'        => [$username, $password],
                        'form_params' => [
                            'phone'       => $user->phone,
                            'source'      => $source,
                            'region_id'   => $user_data->region_id,
                            'region_name' => $user_data->region->title_ru,
                            'sex'         => $user_data->sex,
                            'birth_date'  => Carbon::parse($user_data->birth_date)->format('d.m.Y'),
                            'locale'      => 'ru',
                            'reg_date'    => Carbon::parse($user->created_at)->format('d.m.Y H:i:s'),
                        ],
                    ]);
                } catch (\Exception $ex) {
                    Log::error($ex);
                }
            }
        }
    }
}
