<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\User\UserRepository;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateUserStaffRequest;
use App\Http\Requests\Users\CreateBusinessUserRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Resources\StaffUserResource;
use App\Http\Resources\StaffUsersResource;
use App\Http\Resources\UserResource;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use  Spatie\Permission\Models\Role as SpatieRole;

class UserController extends Controller
{
    public $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    public function getAllPaginatedUserCollection()
    {
        $users = $this->user->getPaginatedCollection();

        return UserResource::collection($users);
    }

    public function getBusinesses()
    {
        $companies = $this->user->getBusinesses();

        return response()->json(["data" => $companies]);
    }
    public function getStaffUsers(Request $request)
    {
        try
        {
            $perPage = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search');

            $userQuery = User::orderByDesc('users.id');
            if (Auth::user()->roles->pluck('name')->first() == 'admin')
            {
               // $userQuery = User::orderBy('id', 'desc');
            }
            else 
            {
                $userQuery = User::join('user_roles', 'user_roles.id', '=', 'users.user_role_id')
                                ->where('users.created_by', Auth::id())
                                ->select('users.*', 'user_roles.name as user_role');
            }

            if ($searchTerm !== null) {
                $userQuery->where(function ($query) use ($searchTerm){
                    $query->where('first_name', 'ILIKE', '%' . $searchTerm . '%')
                          ->orWhere('last_name', 'ILIKE', '%'. $searchTerm . '%')
                          ->orWhere('email', 'ILIKE', '%' . $searchTerm . '%');
                });
            }

            $userQuery->orderBy('created_at', 'desc');

            $users = $userQuery->paginate($perPage, 
            ['id', 'first_name', 'last_name', 'company_name', 'email', 
            'country', 'phone', 'website', 'registration_number',
            'describe', 'created_at', 'updated_at', 'image', 
            'mobile', 'status', 'is_email_verified'], 'page', $page);

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No users found', 'data' => []], 200);
            }
            
            return StaffUsersResource::collection($users);
            // return response()->json(['message' => "Users roles fetched successfully", 
            //                         'data' => ['users' => $users->items(),
            //                         'count' => $users->total(), 
            //                         'rowsPerPage' => $users->perPage(), 
            //                         'currentPage' => $users->currentPage()
            //                         ]], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $user = User::find($id);

        return StaffUserResource::make($user);
    }
    public function createBusinessUser(CreateBusinessUserRequest $request)
    {
        $data = $request->getAndFormatData();

        $user = $this->user->createBusinessUser($data);

        return UserResource::make($user);
    }

    public function store(Request $request)
    {
       try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => 'required|min:8',
                'phone' => 'nullable|numeric',
                "image" => "required|string", 
                'role' => 'required|exists:user_roles,id', //in:manufacturer,seller,retailer,staff// Assuming these are the valid roles  (USER)
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 422);
            }

            $userRole = UserRole::find($request->role);

            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'image'=>$request->image,
                'email_verified_at' => \Carbon\Carbon::now(),
                'created_by' => Auth::user()->id,
                'user_role_id' => $userRole->id
            ]);
            
            Mail::to($user->email)->send(new WelcomeMail($user, $request->password));

            return response()->json([
                'message' => 'User added successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateStatus($id)
    {
        $updateUserStatus = User::find($id);
        if($updateUserStatus->status == "activate"){
            $updateUserStatus->update(["status" => "de-activate"]);
        }else{
            $updateUserStatus->update(["status" => "activate"]);
        }
        return response()->json(["message" => "User activity status updated successfully",
         "data" => [
            "status" => $updateUserStatus->status
            ]
        ]);
    }

    public function update($id, Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            'phone' => 'nullable|string',
            'image' => 'nullable',
            "company_name" => "nullable",
            'role' => 'required|in:manufacturer,seller,retailer|exists:roles,name', //, Rule::in(['manufacturer', 'seller', 'retailer', 'staff']) Add role validation
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        $role = SpatieRole::where("name", $request->role)->first();
        
        $user = User::find($id);

        $validateCompany = User::where("id", "!=", $id)->where("company_name", $request->input('company_name'))->exists();

        if($validateCompany){
            ValidationException::withMessages(["error" => "Company name is already in-use with other Business"]);
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'image'=> (isset($request->image) && $request->image != null) ? $request->image : $user->image,
            'created_by' => Auth::user()->id,
            "company_name" => (isset($request->company_name) && $request->company_name != null) ? $request->input('company_name'): $user->company_name
        ]);

        if($user->hasAnyRole(['manufacturer', 'seller', 'retailer']) === false )
        {
            $user->assignRole($request->role);
        }
        else
        {
            $user->removeRole($user->getRoleNames()[0]);
            $user->assignRole($role->name);
        }

        return response()->json([
            "message" => "User updated successfully.",
            "data" => $user
        ]);

    }

    public function updateStaff(UpdateUserStaffRequest $request, string $id)
    {
        $data = $request->getAndFormatData();

        try {
            $userRole = UserRole::find($data['role']);
            $data['user_role_id'] = $userRole->id;
            $data['created_by'] = Auth::user()->id;

            $user = User::findOrFail($id);
            $user->update($data);
            
            // $user->update($request->only(['first_name', 'lasconver,retailer,staff/rtToUserTimezonet_name', 'email', 'phone','image'])); // Update user details
            
            // if($user->hasAnyRole(['manufacturer', 'seller', 'retailer', 'staff']) === false ){
            //     $user->assignRole($request->role);
            // }else{
            //     $user->removeRole($user->getRoleNames()[0]);
            //     $role = Role::where('id', $request->role)->first(); 
            //     $user->assignRole($role->id);
            // }

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user], 200);
        }
        catch (\Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    
    public function deleteStaff(string $id)
    {
        $user = User::where('id', $id)->where('created_by', Auth::id());
        
        $user->delete();

        return response()->json(['message' => 'User deleted successfully','data' => []],200);
    }

    public function deleteAnyUser($id)
    {
        $user = User::find($id);
        $user->delete();

        $staff = User::where('created_by', $id);
        $exists = $staff->exists();

        if($exists){
            $staff->delete();
        }

        return response()->json(['message' => 'User deleted successfully','data' => []],200);
        
    }
}
