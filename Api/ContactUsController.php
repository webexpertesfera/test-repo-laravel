<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactAdminNotification;
use Illuminate\Support\Facades\Validator;

use App\Models\Contactus;
use OpenApi\Annotations as OA;
use App\Models\User;


class ContactUsController extends Controller
{
    /**
 * @OA\Info(
 *     title="API Title",
 *     version="1.0.0",
 *     description="API Description",
 *     @OA\Contact(
 *         email="contact@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */

    /**
 * @OA\Post(
 *     path="/api/send-message",
 *     summary="Send message to admin",
 *     description="Sends a message to the admin of the application",
 *     tags={"Messaging"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"name", "email", "message"},
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="John Doe",
 *                     description="Name of the sender"
 *                 ),
 *                 @OA\Property(
 *                     property="email",
 *                     type="string",
 *                     format="email",
 *                     example="john@example.com",
 *                     description="Email of the sender"
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     type="string",
 *                     example="Hello, I have a question regarding...",
 *                     description="Message content"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Message sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Message sent to admin successfully"),
 *             @OA\Property(property="data", type="object", example={}),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="field_name", type="array",
 *                     @OA\Items(type="string", example="The field error message.")
 *                 ),
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Something went wrong"),
 *         ),
 *     ),
 * )
 */

    public function sendMessage(Request $request)
{
    try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'message' => 'required|string',
        ]);
         if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $admin = User::findOrFail(1); // Assuming there is always a user with ID 1
        
        // Send email to admin
        $adminEmail = $admin->email; // Replace with your admin's email
        Mail::to($adminEmail)->send(new ContactAdminNotification($request->name, $request->email, $request->message));

        return response()->json(['message' => 'Message sent to admin successfully', 'data' => []], 200);
    } catch (\Exception $e) {
        // Log the error or handle it as needed
        return response()->json(['message' => 'Something went wrong'], 500);
    }
}

/**
     * @OA\Schema(
     *     schema="ContactMessage",
     *     title="Contact Message",
     *     description="Contact Message object",
     *     @OA\Property(
     *         property="name",
     *         type="string",
     *         description="Sender's name"
     *     ),
     *     @OA\Property(
     *         property="email",
     *         type="string",
     *         format="email",
     *         description="Sender's email address"
     *     ),
     *     @OA\Property(
     *         property="message",
     *         type="string",
     *         description="Message content"
     *     ),
     *     @OA\Property(
     *         property="phone",
     *         type="string",
     *         description="Sender's phone number"
     *     ),
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/contact-us",
     *     summary="Contact Us",
     *     description="Sends a message to the admin",
     *     tags={"Contact Us"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Contact Message",
     *         
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message sent to admin successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */


    public function contactUs(Request $request){
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'email' => 'required|string|email|max:255',
                'message' => 'required|string',
                'phone' => 'required',
            ]);

            // Create a new contact record
            Contactus::create([
                'email' => $validatedData['email'],
                'message' => $validatedData['message'],
                'phone' => $validatedData['phone'],
            ]);

            return response()->json(['message' => 'Message sent to admin successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

   
    public function index()
    {
        try {
            // Fetch all contact records
            $contacts = Contactus::all();

            return response()->json(['message' => "Contacts retrieved successfully", 'data' => $contacts], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
