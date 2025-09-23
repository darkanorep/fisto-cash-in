<?

namespace App\Services;
use App\Models\Role;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class RoleService {

    use ApiResponse;

    public function getRoles(Request $request) {
        
        return Role::dynamicPaginate();
    }
    
    public function createRole($data) {
        return Role::create($data);
    }

    public function getRoleById($id) {
        return Role::find($id);
    }

    public function updateRole($role, $data) {
        $role->update($data);
        
        return $role;
    }

    public function changeStatus($id) {
        $role = Role::withTrashed()->find($id);

        if ($role->trashed()) {
            $role->restore();
        } else {
            $role->delete();
        }

        return $role;
    }
    
}