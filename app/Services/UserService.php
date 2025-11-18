<?php

namespace App\Services;

use App\Models\User;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserService
{
    protected $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function getUsers()
    {
        return $this->user->with(['roles',  'charge'])->useFilters()->dynamicPaginate();
    }

    public function createUser($data)
    {
        $user = $this->user->create($data);
        $roleIds = $data['role_id'];
        if (!empty($roleIds)) {
            $roleIds = is_array($roleIds) ? $roleIds : [$roleIds];
            $user->roles()->sync($roleIds);
        }

        return $user;
    }

    public function getUserById($id) 
    {
        return $this->user->with(['roles', 'charge'])->find($id);
    }

    public function updateUser($user, $data)
    {
        $roleIds = $data['role_id'] ?? [];
        unset($data['role_id']);
        
        if (array_key_exists('password', $data) && (is_null($data['password']) || $data['password'] === '')) {
            unset($data['password']);
        }
        
        $user->update($data);
        
        if (!empty($roleIds)) {
            $roleIds = is_array($roleIds) ? $roleIds : explode(',', $roleIds);
            $user->roles()->sync($roleIds);
        }
        
        return $user;
    }

    public function changeStatus($id)
    {
        $user = $this->user->withTrashed()->find($id);

        if ($user->trashed()) {
            $user->restore();
        } else {
            $user->delete();
        }

        return $user;
    }

    public function truncate(): void
    {
        DB::table('role_users')->truncate();
        DB::table('users')->truncate();
    }
}