<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function index(Request $request) {
        $users = $this->userService->getUsers($request);

        $users->getCollection()->transform(function ($item) {
            return new UserResource($item);
        });

        return $this->responseSuccess('Users fetched successfully', $users);
    }

    public function store(UserRequest $request) {
        $data = $request->validated();

        return $this->responseSuccess('User created successfully', $this->userService->createUser($data), 201);
    }

    public function show($id) {
        $user = $this->userService->getUserById($id);
        if (!$user) {
            return $this->responseNotFound('User not found');
        }

        return $this->responseSuccess('User fetched successfully', $user);
    }

    public function update(UserRequest $request, $id) {
        $data = $request->validated();
        $user = $this->userService->getUserById($id);
        if (!$user) {
            return $this->responseNotFound('User not found');
        }
        $updatedUser = $this->userService->updateUser($user, $data);
        return $this->responseSuccess('User updated successfully', $updatedUser);
    }

    public function destroy($id) {
        $user = $this->userService->changeStatus($id);
        if (!$user) {
            return $this->responseNotFound('User not found');
        }

        return $this->responseSuccess('User status changed successfully', $user);
    }
}
