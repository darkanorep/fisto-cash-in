<?php

namespace App\Services;

use App\Models\User;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Client\Request;

class UserService
{
    use ApiResponse;

    protected $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function getUsers(Request $request)
    {
        return $this->user->dynamicPaginate();
    }

    public function createUser($data)
    {
        return $this->user->create($data);
    }

    public function getUserById($id) 
    {
        return $this->user->find($id);
    }

    public function updateUser($user, $data)
    {
        $user->update($data);
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
}