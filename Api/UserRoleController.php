<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\User\UserRepository;
use Illuminate\Http\Request;
use App\Models\UserRole;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use App\Http\Resources\UserRoleResource;
use App\Models\User;
use App\Models\UserRolePermission;
use Illuminate\Validation\ValidationException;

class UserRoleController extends Controller
{
    public $userRoles;

    public function __construct(UserRepository $userRoles)
    {
        $this->userRoles = $userRoles;
    }
    
    public function index()
    {
        try 
        {
            $userRoles = $this->userRoles->getPaginatedUserRoles();

            return response()->json([
                'message' => "Users roles retrieved successfully",
                'data' =>[
                    'roles'=>$userRoles->items(),
                    'count' => $userRoles->total(),
                    'rowsPerPage' => $userRoles->perPage(), 
                    'currentPage' => $userRoles->currentPage()]
                ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function show($id)
    {
        $userRole = UserRole::leftJoin('user_roles_permissions', 'user_roles_permissions.user_role_id', '=', 'user_roles.id')
        ->where('user_roles.id', $id)
        ->select('user_roles.*')->first();
        
        return UserRoleResource::make($userRole);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleName' => 'required|string',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string',
            'permissions.*.permissions' => 'required|array',
            'permissions.*.permissions.view' => 'required|boolean',
            'permissions.*.permissions.add' => 'required|boolean',
            'permissions.*.permissions.edit' => 'required|boolean',
            'permissions.*.permissions.delete' => 'required|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'data' => []], 422);
        }

        try
        {
        	$role = UserRole::firstOrCreate(['name' => $request->roleName, 'company_id' => Auth::id()]);

            // Process permissions
            foreach ($request->permissions as $permissionData)
            {
                $permissionName = $permissionData['name'];
                $permissions = $permissionData['permissions'];
                
                foreach ($permissions as $permissionKey => $permissionValue)
                {
                    if($permissionValue === true)
                    {
                        $permissionFullName = $permissionName.'_'.$permissionKey;
                        $checkRolePermission = UserPermission::where('name', $permissionFullName)->first();

                        if(!empty($checkRolePermission)){
                            UserRolePermission::firstOrCreate([
                                "user_role_id" => $role->id,
                                "user_permission_id" => $checkRolePermission->id
                            ]);
                        }
                    }
                }
            }
            $permission = UserPermission::where('name', $permissionFullName)->first();
        
		    return response()
                    ->json(['message' => 'Role and permissions assigned successfully', 'data' => $role]);
	    }
	    catch (\Exception $e)
	    { 
            return response()->json(["message" => $e->getMessage()]);      
	    }
	}
   
    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleName' => 'required|string|exists:user_roles,name',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string',
            'permissions.*.permissions' => 'required|array',
            'permissions.*.permissions.view' => 'required|boolean',
            'permissions.*.permissions.add' => 'required|boolean',
            'permissions.*.permissions.edit' => 'required|boolean',
            'permissions.*.permissions.delete' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'data' => []], 422);
        }

        try {
        	$role = UserRole::findOrFail($id);
            $role->update(['name' => $request->roleName]);
        
        	UserRolePermission::where("user_role_id", $id)->delete();
        
            foreach ($request->permissions as $permissionData)
            {
                $permissionName = $permissionData['name'];
                $permissions = $permissionData['permissions'];
                
                foreach ($permissions as $permissionKey => $permissionValue)
                {

                    if ($permissionValue === true)
                    {
                        $permissionFullName = $permissionName . '_' . $permissionKey;
                        //$checkRolePermission = UserPermission::where('name', $permissionFullName)->first();

                        $checkRolePermission = UserPermission::updateOrCreate(["name" => $permissionFullName], ["name"=> $permissionFullName]);

                        if(!empty($checkRolePermission)){
                            UserRolePermission::firstOrCreate([
                                "user_role_id" => $role->id,
                                "user_permission_id" => $checkRolePermission->id
                            ]);
                        }
                    }
                } 
            }
		
            $permission = UserPermission::where('name', $permissionFullName)->first();
            
            return response()->json([
            'message' => 'Role and permissions assigned successfully', 
            'data' => $role]);
        }
	   catch (\Exception $e)
	   {
		return response()->json(['message' => $e->getMessage(), 'data' => []], 500);
	    }
     }


    public function destroy($id)
    {
        try {
            $checkIsUserRoleInUse = User::where("user_role_id", $id)->exists();
            
            if($checkIsUserRoleInUse){
                throw ValidationException::withMessages(['message'=> "User role is already in use"]);
            }
            $role = UserRole::find($id);

            if (!$role) {
                return response()->json(['message' => 'Role not found'], 404);
            }

            $role->delete();

            return response()->json(['message' => 'Role deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete role', 'data' => []], 500);
        }
    }
}

