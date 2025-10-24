<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Services\PermissionService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Resources\PermissionResource;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionController extends Controller
{
    use ApiResponse;
    protected $permissionService;

    // Dependency Injection
    public function __construct(PermissionService $permissionService) {
        $this->permissionService = $permissionService;
    }

    // List Permissions with Pagination and Filtering
    public function index(Request $request) {
        $permissions = $this->permissionService->getPermissions($request);

        return $permissions instanceof LengthAwarePaginator
            ? $permissions->setCollection($permissions->getCollection()->transform(function ($item) {
                    return new PermissionResource($item);
                })) 
            : $permissions = PermissionResource::collection($permissions);

        return $permissions->isEmpty()
            ? $this->responseNotFound('No Permissions found.')
            : $this->responseSuccess('Permissions fetched successfully', $permissions);
    }

    // Create a new Permission
    public function store(PermissionRequest $request) {
        $data = $request->validated();
        return $this->responseCreated('Permission created successfully', new PermissionResource($this->permissionService->createPermission($data)));
    }

    // Get a specific Permission by ID
    public function show($id) {

        if (!$this->permissionService->getPermissionById($id)) {
            return $this->responseNotFound('Permission not found');
        }

        return $this->responseSuccess('Permission fetched successfully', new PermissionResource($this->permissionService->getPermissionById($id)));
    }

    // Update an existing Permission
    public function update(PermissionRequest $request, $id) {

        $data = $request->validated();
        $permission = $this->permissionService->getPermissionById($id);
        if (!$permission) {
            return $this->responseNotFound('Permission not found');
        }

        return $this->responseSuccess('Permission updated successfully', $this->permissionService->updatePermission($permission, $data));
    }

    // Soft Delete (Change Status) of a Permission
    public function destroy($id) {
        $permission = $this->permissionService->changeStatus($id);
        if (!$permission) {
            return $this->responseNotFound('Permission not found');
        }

        return $this->responseSuccess('Permission status changed successfully', $permission);
    }

}
