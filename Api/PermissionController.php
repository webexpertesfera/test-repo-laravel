<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
class PermissionController extends Controller
{

    public function index()
    {
        try {
            $permissions = Permission::all();
            
            return response()->json(['message'=>'Permissions fetch successfully','data' => $permissions], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        
        $validator = Validator::make($request->all(), [
            '*.name' => 'required|string|exists:permissions,name',
            '*.permissions' => 'required|array',
            '*.permissions.view' => 'required|boolean',
            '*.permissions.create' => 'required|boolean',
            '*.permissions.edit' => 'required|boolean',
            '*.permissions.delete' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid input data','data'=>[]], 400);
        }

        try {

            $user = User::findOrFail($id);

            foreach ($request->all() as $permissionItem) {
                // Get the permission
                $permission = Permission::where('name', $permissionItem['name'])->firstOrFail();

                // Update the permissions for the user based on the permissions provided
                $user->syncPermissions([
                    $permission->name => [
                        'view' => $permissionItem['permissions']['view'],
                        'create' => $permissionItem['permissions']['create'],
                        'edit' => $permissionItem['permissions']['edit'],
                        'delete' => $permissionItem['permissions']['delete'],
                    ]
                ]);
            }

            return response()->json(['message' => 'Permissions updated successfully','data'=>[]], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found','data'=>[]], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update permissions','data'=>[]], 500);
        }
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            '*.name' => 'required|string|exists:permissions,name',
            '*.permissions' => 'required|array',
            '*.permissions.view' => 'required|boolean',
            '*.permissions.create' => 'required|boolean',
            '*.permissions.edit' => 'required|boolean',
            '*.permissions.delete' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input data','data'=>[]], 400);
        }

        try {
            $user = User::findOrFail($request->id);

            // Iterate through each permission item
            foreach ($request->all() as $permissionItem) {

                $permission = Permission::where('name', $permissionItem['name'])->firstOrFail();

                $user->givePermissionTo([
                    $permission->name => [
                        'view' => $permissionItem['permissions']['view'],
                        'create' => $permissionItem['permissions']['create'],
                        'edit' => $permissionItem['permissions']['edit'],
                        'delete' => $permissionItem['permissions']['delete'],
                    ]
                ]);
            }

            return response()->json(['message' => 'Permissions updated successfully','data'=>$user], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found','data'=>[]], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update permissions','data'=>[]], 500);
        }
    }


    public function delete(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid input data', 'data' => []], 400);
        }

        try {
            $user = User::findOrFail($id);

            // Get the permission
            $permissionName = $request->input('permission_name');
            $permission = Permission::where('name', $permissionName)->firstOrFail();

            // Revoke the permission from the user
            $user->revokePermissionTo($permission);

            return response()->json(['message' => 'Permission revoked successfully', 'data' => []], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User or permission not found', 'data' => []], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to revoke permission', 'data' => []], 500);
        }
    }
}
