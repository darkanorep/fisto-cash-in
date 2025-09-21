<?

namespace App\Services;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleService {

    public function getRoles(Request $request) {
        
        return Role::get()->toArray();
    }
    
    public function createRole($data) {
        return Role::create($data);
    }

    public function getRoleById($id) {
        return Role::find($id);
    }

    public function updateRole($role, $data) {
        $role->update($data);
        
        return response()->json(['message' => 'Role updated successfully', 'role' => $role], 200);
    }

    public function changeStatus($id) {
        $role = Role::withTrashed()->find($id);

        if ($role->trashed()) {
            $role->restore();
        } else {
            $role->delete();
        }

        return response()->json(['message' => 'Role status changed successfully'], 200);
    }
    
}