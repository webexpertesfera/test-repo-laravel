<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use OpenApi\Annotations as OA;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;


class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'company_name' => 'nullable|max:255',
                'email' => 'required|email|unique:users|max:255',
                'password' => 'required|min:8',
                'country' => 'required|string|max:255',
                'phone' => 'required|max:15',
                'website' => 'nullable',
                'registration_number' => 'nullable',
                'describe' => 'nullable',
                'image' => 'nullable',
                'mobile' => 'nullable',
                'role' => 'required|in:manufacturer,seller,retailer|exists:roles,name'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $user = User::create([
                'name'=>$request->first_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'country' => $request->country,
                'phone' => $request->phone,
                'website' => $request->website,
                'registration_number' => $request->registration_number,
                'describe' => $request->describe,
                'image' => $request->image,
                'mobile' => $request->mobile,
            ]);

            $user->assignRole($request->role);
            $token = $user->createToken('Fouadecommerceapp')->accessToken;
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $th) {
                //throw $th;
            }

            return response()->json([
                'message' => 'User registered successfully',
                'data' => ['access_token' => $token],
            ], 201);
        } catch (\Exception $e) {
            //return $e;
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function verify(Request $request)
    {
        try {
            $expires = $request->expires;
            $id = $request->id;
            $hash = $request->hash;
            $signature = $request->signature;

            // Verify the signature to ensure the URL hasn't been tampered with
            if (!URL::hasValidSignature($request)) {
                return redirect()->to(env('FRONT_URL') . '/auth/email-error?error=invalid_signature');
            }

            // Check if the URL has expired
            if (time() > $expires) {
                return redirect()->to(env('FRONT_URL') . '/auth/email-error?error=url_expired');
            }

            // Find the user by ID
            $user = User::find($id);

            if (!$user) {
                return redirect()->to(env('FRONT_URL') . '/auth/email-error?error=user_not_found');
            }

            // Mark the email as verified
            if ($user->hasVerifiedEmail()) {
                return redirect()->to(env('FRONT_URL') . '/auth/email-success');
            }

            $user->markEmailAsVerified();

            return redirect()->to(env('FRONT_URL') . '/auth/email-success');
        } catch (\Exception $e) {
            return redirect()->to(env('FRONT_URL') . '/auth/email-error?error=server_error');
        }
    }


    public function resendVerificationEmail(Request $request)
    {
        try {
            $identifier = $request->input('email');

            // Find the user by identifier
            $user = User::where('email', $identifier)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email address is already verified.'], 400);
            }

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            return response()->json(['message' => 'Verification email resent successfully.','data'=>[]], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function login(Request $request)
    {
        try {
            $input = $request->all();
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
                'role' => 'nullable'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $credentials = $request->only('email', 'password');

            $user = User::withTrashed()->where('email', $request->email)->first();

            if (!empty($user)) {
                if($user->deleted_at != null){
                    return response()->json(['message' => 'You are temporarly shudown by administration, contact to administration for continue.'], 400);
                }
            }else{
                return response()->json(['message' => 'User not found!'], 400);
            }

            if($user->status === "de-activate"){
                return response()->json([
                    'status' => 400,
                    'message' => "You are temporarly blocked, try to contact administration."], 400);
            }
            
            if (!$user->email_verified_at) {
                return response()->json(['message' => 'Email not verified!'], 400);
            }

            $role = isset($input['role']) ? $input['role'] : 'manufacturer';

            if($role == "admin" && !$user->hasRole("admin")){
                return response()->json(['message' => 'Invalid role! You are not a ' . $role], 400);
            }

            if($role != "admin" && $user->hasRole("admin")){
                return response()->json(['message' => 'Invalid role! You are cannot login'], 400);
            }

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('foaudapp')->accessToken;
                return response()->json([
                    'message' => 'login successfully',
                    'data' => [
                        'access_token' => $token,
                        'user' => $user
                    ]
                ], 200);
            }

            return response()->json(['message' => 'Invalid credentials'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function requestMagicLink(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $token = Str::random(60);
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->update(['magic_link_token' => $token]);

            // Constructing the magic link manually
            $frontendUrl = env('FRONT_URL');
            $expires = now()->addMinutes(30)->getTimestamp();
            $signature = hash_hmac('sha256', $token . $expires, env('APP_KEY'));

            $token = $user->createToken('foaudapp')->accessToken;
            $magicLink = $frontendUrl . '?token=' . $token;
            Mail::send([], [], function ($message) use ($request, $token) {
                // Define the base URL of your frontend app
                $frontendUrl = env('FRONT_URL');

                // Create the query string with the token
                $queryString = http_build_query(['token' => $token]);

                // Construct the full URL without the leading http://localhost:5173/
                $magicLink = rtrim($frontendUrl, '/') . '/?' . $queryString;

                $message->to($request->email)
                    ->subject('Magic Link To App')
                    ->html('<p>Click the button below to login:</p><a href="' . $magicLink . '"><button style="background-color: #4CAF50; /* Green */
                            border: none;
                            color: white;
                            padding: 15px 32px;
                            text-align: center;
                            text-decoration: none;
                            display: inline-block;
                            font-size: 16px;
                            margin: 4px 2px;
                            cursor: pointer;">Login</button></a>');
                                });



            return response()->json(['message' => 'Magic link sent to your email', 'data' => []], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }


    public function loginWithMagicLink(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $user = User::where('magic_link_token', $request->token)->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid token'], 401);
            }

            if(!$user->email_verified_at)
            {
                return response()->json([
                    "message" => "Email is not verified."
                ], 401);
            }

            $user->update(['magic_link_token' => null]);

            auth()->login($user);

            $accessToken = $user->createToken('Fouadecommerceapp')->accessToken;

            return response()->json(['message' => 'login successfull', 'data' => [$accessToken]]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
            // return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? response()->json(['message' => __($status), 'data' => []], 200)
                : response()->json(['message' => __($status)], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => bcrypt($password)
                    ])->save();
                }
            );

            return $status == Password::PASSWORD_RESET
                ? response()->json(['message' => __($status), 'data' => []], 200)
                : response()->json(['message' => __($status)], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        // Logout the authenticated user
        Auth::logout();

        // Optionally, invalidate the user's session
        $request->session()->invalidate();

        // Return a response indicating successful logout
        return response()->json(['message' => 'Successfully logged out', 'data' => []]);
    }
}
