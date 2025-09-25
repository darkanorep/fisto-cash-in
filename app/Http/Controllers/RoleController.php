<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;
    protected $roleService;

    public function __construct(RoleService $roleService) {
        $this->roleService = $roleService;
    }

    public function index(Request $request) {
        $roles = $this->roleService->getRoles($request);
        
        $roles->getCollection()->transform(function ($role) {
            return new RoleResource($role);
        });
        
        return $this->responseSuccess('Roles fetched successfully', $roles);
    }

    public function store(RoleRequest $request) {
        $data = $request->validated();
        return $this->responseSuccess('Role created successfully', $this->roleService->createRole($data), 201);
    }

    public function show($id) {

        if (!$this->roleService->getRoleById($id)) {
            return $this->responseNotFound('Role not found');
        }

        return $this->responseSuccess('Role fetched successfully', new RoleResource($this->roleService->getRoleById($id)));
    }

    public function update(RoleRequest $request, $id) {
        $data = $request->validated();

        $role = $this->roleService->getRoleById($id);
        if (!$role) {
            return $this->responseNotFound('Role not found');
        }

        return $this->responseSuccess('Role updated successfully', $this->roleService->updateRole($role, $data));
    }

    public function destroy($id) {
        return $this->responseSuccess('Role changed status successfully', $this->roleService->changeStatus($id));
    }
}
