<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionService
{
    public function getPermissions(Request $request) {
        return Permission::dynamicPaginate();
    }

    public function createPermission($data) {
        return Permission::create($data);
    }

    public function getPermissionById($id) {
        return Permission::find($id);
    }

    public function updatePermission($Permission, $data) {
        $Permission->update($data);
        
        return $Permission;
    }

    public function changeStatus($id) {
        $Permission = Permission::withTrashed()->find($id);

        if ($Permission->trashed()) {
            $Permission->restore();
        } else {
            $Permission->delete();
        }

        return $Permission;
    }
}