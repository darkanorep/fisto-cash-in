<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    use ApiResponse;
    private $userService;

    // Dependency Injection
    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    // List Users with Pagination and Filtering
    public function index(Request $request) {
        $users = $this->userService->getUsers($request);

        $users instanceof LengthAwarePaginator
            ? $users->setCollection($users->getCollection()->transform(function ($item) {
                    return new UserResource($item);
                })) 
            : $users = UserResource::collection($users);

        return $users->isEmpty()
            ? $this->responseNotFound('No users found.')
            : $this->responseSuccess('Users fetched successfully', $users);
    }

    // Create a new User
    public function store(UserRequest $request) {
        $data = $request->validated();

        return $this->responseCreated('User created successfully', new UserResource($this->userService->createUser($data)));
    }

    // Get a specific User by ID
    public function show($id) {
        if (!$this->userService->getUserById($id)) {
            return $this->responseNotFound('User not found');
        }

        return $this->responseSuccess('User fetched successfully', new UserResource($this->userService->getUserById($id)));
    }

    // Update an existing User
    public function update(UserRequest $request, $id) {
        $data = $request->validated();
        $user = $this->userService->getUserById($id);
        if (!$user) {
            return $this->responseNotFound('User not found');
        }
        $updatedUser = $this->userService->updateUser($user, $data);
        return $this->responseSuccess('User updated successfully', new UserResource($updatedUser));
    }

    // Soft Delete (Change Status) of a User
    public function destroy($id) {
        $user = $this->userService->changeStatus($id);
        if (!$user) {
            return $this->responseNotFound('User not found');
        }

        return $this->responseSuccess('User status changed successfully', $user);
    }

    // Truncate Users and Roles
    public function truncate() {

        $this->userService->truncate();
        return $this->responseSuccess('All users deleted successfully');
    }}
