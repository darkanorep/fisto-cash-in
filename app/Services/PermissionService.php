<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionService
{
    protected $permission;

    public function __construct(Permission $permission) {
        $this->permission = $permission;
    }
    
    public function getPermissions(Request $request) {
        return $this->permission->with('role')->useFilters()->dynamicPaginate();
    }

    public function createPermission($data) {
        return $this->permission->create($data);
    }

    public function getPermissionById($id) {
        return $this->permission->with('role')->find($id);
    }

    public function updatePermission($Permission, $data) {
        $Permission->update($data);
        
        return $Permission;
    }

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