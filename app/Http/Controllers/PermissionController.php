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

        if ($permissions->isEmpty()) {
            return $this->responseNotFound('No Permissions found.');
        }

        return $permissions instanceof LengthAwarePaginator
            ? $permissions->through(fn($item) => new PermissionResource($item))
            : $this->responseSuccess('Permissions fetched successfully', PermissionResource::collection($permissions));
    }

    // Create a new Permission
//    public function store(PermissionRequest $request) {
//        $data = $request->validated();
//        return $this->responseCreated('Permission created successfully', new PermissionResource($this->permissionService->createPermission($data)));
//    }

    public function store(PermissionRequest $request) {
        $data = $request->validated();
        $permissions = $this->permissionService->createPermission($data);

        return $this->responseCreated(
            'Permissions created successfully',
            PermissionResource::collection($permissions)
        );
    }

    // Get a specific Permission by ID
    public function show($id) {

        if (!$this->permissionService->getPermissionById($id)) {
            return $this->responseNotFound('Permission not found');
        }

        return $this->responseSuccess('Permission fetched successfully', new PermissionResource($this->permissionService->getPermissionById($id)));
    }

    // Update an existing Permission
//    public function update(PermissionRequest $request, $id) {
//
//        $data = $request->validated();
//        $permission = $this->permissionService->getPermissionById($id);
//        if (!$permission) {
//            return $this->responseNotFound('Permission not found');
//        }
//
//        return $this->responseSuccess('Permission updated successfully', $this->permissionService->updatePermission($permission, $data));
//    }

    public function update(PermissionRequest $request, $id) {
        $data = $request->validated();
        $permission = $this->permissionService->getPermissionById($id);

        if (!$permission) {
            return $this->responseNotFound('Permission not found');
        }

        $updatedPermissions = $this->permissionService->updatePermission($permission, $data);

        // Handle both single permission and collection
        $resource = is_array($updatedPermissions)
            ? PermissionResource::collection($updatedPermissions)
            : new PermissionResource($updatedPermissions);

        return $this->responseSuccess('Permission updated successfully', $resource);
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
