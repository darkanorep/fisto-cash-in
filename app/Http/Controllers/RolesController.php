<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService) {
        $this->roleService = $roleService;
    }

    public function index(Request $request) {
        return $this->roleService->getRoles($request)->paginate(10);
    }

    public function store(RoleRequest $request) {
        $data = $request->validated();
        return $this->roleService->createRole($data);
    }

    public function show($id) {
        return $this->roleService->getRoleById($id);
    }

    public function update(RoleRequest $request, $id) {
        $data = $request->validated();

        $role = $this->roleService->getRoleById($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return $this->roleService->updateRole($role, $data);
    }

    public function destroy($id) {
        return $this->roleService->changeStatus($id);
    }

    //test
}
