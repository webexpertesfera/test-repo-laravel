<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\ImageUploader;
use App\Http\Requests\Auth\ResetAuthPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Illuminate\Http\Request;
use App\Models\User;

class ProfileController extends Controller
{

    public $imageUploader;

    public function __construct(ImageUploader $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }

    public function show()
    {
       $user = User::with('assignedUserRole')->find(Auth::user()->id);

       return ProfileResource::make($user);
        // return response()->json([
        //     'message' => 'get user data successfully',
        //     'data' => ["user" => $user]
        // ],200);
    }


    // public function upload(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'image' => 'required|file|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()->first()], 422);
    //     }

    //     $user = User::find(Auth::user()->id);

    //     $existedImage = DB::table('users')->where('id', Auth::user()->id)->pluck('image')->first();
        
    //     if($existedImage != null && $request->hasFile('image')){
    //         if(Storage::disk('public')->exists("/images/".$existedImage))
    //         {
    //             Storage::disk('public')->delete("/images/".$existedImage);
    //         }
    //     }

    //     $imageName = time().'.'.$request->image->extension();  
    //     $fullPath = "images/".$imageName;
        
    //     Storage::disk("public")->putFileAs('images', $request->file('image'), $imageName);

    //     $user->update(['image' => $imageName]);

    //     return response()->json([
    //         'message' => 'Image uploaded successfully.', 
    //         'data' =>[
    //             "image" => $imageName
    //             ]
    //     ],200);
    // }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'image' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $imageName = time().'_-_'.$request->file('image')->getClientOriginalName();

        Storage::disk('public')->putFileAs("/images/", $request->file('image'), $imageName);

        //$imageName = time().'.'.$request->image->extension();  
        //$request->image->storeAs('public/images', $imageName);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'data' =>[
                'image' => $imageName,
                //'image_url'=> asset('storage/images/' . $imageName)
                'image_url' => $this->imageUploader->publicStorageImageUrl($imageName)
                ]
        ],200);
    }

    public function resetPassword(ResetAuthPasswordRequest $request)
    {
        $data = $request->getAndFormatData();

        $resetPassword = User::find(Auth::id());

        $resetPassword->update($data);

        return ProfileResource::make($resetPassword);
    }


    public function update(UpdateProfileRequest $request)
    {
        $data = $request->getAndFormatData();

        $user = User::find(Auth::id());

        // $validator = Validator::make($request->all(), [
        //     //'name' => 'sometimes|string|max:255',
        //     'first_name' => 'sometimes|string|max:255',
        //     'last_name' => 'sometimes|string|max:255',
        //     'company_name' => 'sometimes|string|max:255',
        //     'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
        //     'country' => 'sometimes|string|max:255',
        //     'phone' => 'sometimes|string|max:255',
        //     'website' => 'sometimes|string|max:255',
        //     'registration_number' => 'sometimes|string|max:255',
        //     'describe' => 'sometimes|string',
        //    // 'image' => 'required|file', // Max file size: 2MB
        //     'image' => 'sometimes|string',
        //     'mobile' => 'sometimes|string|max:255',
        // ]);
        // if ($validator->fails()) {
        //     return response()->json(['message' => $validator->errors()->first()], 422);
        // }

        //$data = $request->all();

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'=>[]
        ],200);
    }


    public function Imagedelete(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        $imagePath = 'public/images/' . $request->image;

        if (Storage::exists($imagePath)) {
            Storage::delete($imagePath);
            return response()->json(['message' => 'Image deleted successfully','data'=>[]],200);
        }

        return response()->json(['message' => 'Image not found','data'=>[]], 404);
    }
}
