<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionService
{
    protected $permission;

    public function __construct(Permission $permission) {
        $this->permission = $permission;
    }

    public function getPermissions(Request $request) {
        return $this->permission->with('role')->orderBy('updated_at', 'desc')->useFilters()->dynamicPaginate();
    }

    public function createPermission($data) {
        return $this->permission->create($data);
    }

//    public function createPermission($data) {
//        $names = $data['name'];
//        $roleId = $data['role_id'] ?? null;
//
//        $permissions = [];
//        foreach ($names as $name) {
//            $permissions[] = $this->permission->create([
//                'name' => $name,
//                'role_id' => $roleId
//            ]);
//        }
//
//        return $permissions;
//    }


    public function getPermissionById($id) {
        return $this->permission->with('role')->find($id);
    }

    public function updatePermission($Permission, $data) {
        $Permission->update($data);

        return $Permission;
    }

//    public function updatePermission($permission, $data) {
//        // If name is an array, we need to handle multiple names
//        if (is_array($data['name'])) {
//            // Delete the old permission and create new ones
//            $permission->delete();
//
//            $permissions = [];
//            $roleId = $data['role_id'] ?? null;
//
//            foreach ($data['name'] as $name) {
//                $permissions[] = $this->permission->create([
//                    'name' => $name,
//                    'role_id' => $roleId
//                ]);
//            }
//
//            return $permissions;
//        }
//
//        // Single name update
//        $permission->update($data);
//        return $permission;
//    }


    public function changeStatus($id) {
        $permission = $this->permission->withTrashed()->find($id);

        if ($permission->trashed()) {
            $permission->restore();
        } else {
            $permission->delete();
        }

        return $permission;
    }
}
