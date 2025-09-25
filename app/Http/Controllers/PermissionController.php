<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Services\PermissionService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Resources\PermissionResource;

class PermissionController extends Controller
{
    use ApiResponse;
    protected $permissionService;

    public function __construct(PermissionService $permissionService) {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request) {
        $permissions = $this->permissionService->getPermissions($request);

        $permissions->getCollection()->transform(function ($permission) {
            return new PermissionResource($permission);
        });

        return $this->responseSuccess('Permissions fetched successfully', $permissions);
    }

    public function store(PermissionRequest $request) {
        $data = $request->validated();
        return $this->responseSuccess('Permission created successfully', $this->permissionService->createPermission($data), 201);
    }

    public function show($id) {

        if (!$this->permissionService->getPermissionById($id)) {
            return $this->responseNotFound('Permission not found');
        }

        return $this->responseSuccess('Permission fetched successfully', new PermissionResource($this->permissionService->getPermissionById($id)));
    }

    public function update(PermissionRequest $request, $id) {
        $data = $request->validated();

        $permission = $this->permissionService->getPermissionById($id);
        if (!$permission) {
            return $this->responseNotFound('Permission not found');
        }

        return $this->responseSuccess('Permission updated successfully', $this->permissionService->updatePermission($permission, $data));
    }

    public function destroy($id) {
        return $this->responseSuccess('Permission changed status successfully', $this->permissionService->changeStatus($id));
    }

}
