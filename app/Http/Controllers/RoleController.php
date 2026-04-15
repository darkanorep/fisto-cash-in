<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleController extends Controller
{
    use ApiResponse;
    protected $roleService;

    // Dependency Injection
    public function __construct(RoleService $roleService) {
        $this->roleService = $roleService;
    }

    // List Roles with Pagination and Filtering
    public function index(Request $request) {
        $roles = $this->roleService->getRoles($request);

        if ($roles->isEmpty()) {
            return $this->responseNotFound('No Roles found.');
        }

        return $roles instanceof LengthAwarePaginator
            ? $roles->through(fn($item) => new RoleResource($item))
            : $this->responseSuccess('Roles fetched successfully', RoleResource::collection($roles));
    }


    // Create a new Role
    public function store(RoleRequest $request) {
        $data = $request->validated();
        return $this->responseCreated('Role created successfully', new RoleResource($this->roleService->createRole($data)));
    }

    // Get a specific Role by ID
    public function show($id) {

        if (!$this->roleService->getRoleById($id)) {
            return $this->responseNotFound('Role not found');
        }

        return $this->responseSuccess('Role fetched successfully', new RoleResource($this->roleService->getRoleById($id)));
    }

    // Update an existing Role
    public function update(RoleRequest $request, $id) {
        $data = $request->validated();
        $role = $this->roleService->getRoleById($id);
        if (!$role) {
            return $this->responseNotFound('Role not found');
        }

        return $this->responseSuccess('Role updated successfully', $this->roleService->updateRole($role, $data));
    }

    // Soft Delete (Change Status) of a Role
    public function destroy($id) {
        $role = $this->roleService->changeStatus($id);
        if (!$role) {
            return $this->responseNotFound('Role not found');
        }

        return $this->responseSuccess('Role status changed successfully', $role);
    }
}
