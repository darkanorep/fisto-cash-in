<?

namespace App\Services;
use App\Models\Role;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class RoleService {

    use ApiResponse;
    protected $role;

    public function __construct(Role $role) {
        $this->role = $role;
    }

    public function getRoles(Request $request) {

        return $this->role->useFilters()->dynamicPaginate();
    }
    
    public function createRole($data) {
        return $this->role->create($data);
    }

    public function getRoleById($id) {
        return $this->role->find($id);
    }

    public function updateRole($role, $data) {
        $role->update($data);
        
        return $role;
    }

    public function changeStatus($id) {
        $role = $this->role->withTrashed()->find($id);

        if ($role->trashed()) {
            $role->restore();
        } else {
            $role->delete();
        }

        return $role;
    }
    
}