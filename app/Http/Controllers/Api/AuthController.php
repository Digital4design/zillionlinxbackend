<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\{LoginRequest, RegisterRequest};
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use App\Models\Bookmark;
use App\Models\UserBookmark;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Stevebauman\Location\Facades\Location;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function adminLogin(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password) || $user->role_id != 1) {
            return error('Invalid admin credentials', 401);
        }

        $token = $user->createToken('admin_token')->plainTextToken;

        $responseData = [
            'success' => true,
            'message' => 'Admin login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'name' => "{$user->first_name} {$user->last_name}",
                    'role' => 'admin',
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'country' => $user->country,
                    'role_id' => $user->role_id,
                ]
            ],

        ];

        return response()->json($responseData);
    }


    public function login(LoginRequest $request)
    {
        $type = $request->input('type');
        $user = null; // Initialize user variable

        if ($type === 'google') {
            $response = $this->authService->googleLogin($request->input('google_token'));

            if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() === 200) {
                $responseData = $response->getData(true);

                if (!empty($responseData['status']) && $responseData['status'] === 'success') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Logged in Successfully!',
                        'data' => $responseData['data']
                    ]);
                }
            }
        } elseif ($type === 'email') {
            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            if ($user && $user->password === null) {
                return error('You have previously signed in with Google. Please use Google to log in or reset your password.');
            }

            $response = $this->authService->emailLogin(
                $request->input('email'),
                $request->input('password')
            );

            if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() === 200) {
                $user = User::where('email', $email)->first();
            }
        } else {
            return error("Invalid login type");
        }

        // Convert response to an array
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $responseData = $response->getData(true);
        } else {
            $responseData = (array) $response;
        }

        $ip = request()->ip();
        $location = Location::get($ip);

        $timezone = $location && $location->timezone ? $location->timezone : 'UTC';

        $lastLoginTime = Carbon::now($timezone);
        User::where('email', $request->input('email'))
            ->update(['last_login_at' =>  $lastLoginTime]);


        if (!empty($responseData['status']) && $responseData['status'] === 'success' && $user) {
            return response()->json([
                'success' => true,
                'message' => $responseData['message'] ?? 'Login successful',
                'data' => $responseData['data'],
                'user' => $user, // Include full user details here
            ]);
        }

        return response()->json($responseData);
    }




    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            // Create a new user
            $data = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'country' => $request->input('country'),
                'terms_condition' => $request->input('terms_condition'),
                // 'display_name' => $validated['display_name'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'provider' => 'email',
                // 'role_id' => 2,  // You can adjust the role as needed
            ]);

            $BookmarkData = Bookmark::join('user_bookmarks', 'user_bookmarks.bookmark_id', '=', 'bookmarks.id')
                ->where('bookmarks.default', 'yes')
                ->select(
                    'bookmarks.title',
                    'bookmarks.website_url',
                    'bookmarks.icon_path',
                    'user_bookmarks.category_id',
                    'user_bookmarks.sub_category_id',
                    'user_bookmarks.add_to',
                    'user_bookmarks.pinned'
                )
                ->get();

            foreach ($BookmarkData as $getData) {
                $bookmarkCreated =  Bookmark::create([
                    'title' => $getData->title,
                    'website_url' => $getData->website_url,
                    'icon_path' => $getData->icon_path,
                    'user_id' => $data->id,

                ]);
                UserBookmark::create([
                    'bookmark_id' => $bookmarkCreated->id,
                    'category_id' => $getData->category_id,
                    'sub_category_id' => $getData->sub_category_id,
                    'add_to' => $getData->add_to,
                    'pinned' => $getData->pinned,
                    'user_id' => $data->id,
                ]);
            }


            Auth::login($data);
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;
            $fetch = ["name" => $user->first_name, "display_name" => $user->display_name];




            return success("Register successful", ['token' => $token, 'user' => $fetch]);
        } catch (\Exception $ex) {
            return error($ex->getMessage());
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // Get the user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Generate a secure token
        $token = Str::random(64); // More secure

        // Store reset token in the database (ensure old tokens are deleted first)
        DB::table('password_resets')->where('email', $request->email)->delete();
        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Hash::make($token), // Secure the token
            'created_at' => Carbon::now(),
        ]);

        // Generate frontend reset password link
        $frontendUrl = env('FRONTEND_URL');
        $resetLink = "$frontendUrl/reset-password?token=$token&email=" . urlencode($request->email);

        // Send email
        Mail::to($request->email)->send(new ResetPasswordMail($resetLink));

        return response()->json(['message' => 'Reset link sent to your email.'], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        // Find token in database
        $resetData = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$resetData || !Hash::check($request->token, $resetData->token)) {
            return response()->json(['message' => 'Invalid token or email.'], 400);
        }

        // Update password
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete reset token after use
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }

    public function sendTestEmail()
    {
        // $message = "yessssss";
        Mail::raw('This is a test email', function ($message) {
            $message->to('kartik.d4d@gmail.com')
                ->subject('Test Email')
                ->from('info@pmtool.digital4design.com');
        });

        return response()->json(['message' => 'Test email sent successfully!']);
    }

    public function redirect()
    {
        return Socialite::driver('google')->redirect()->getTargetUrl();
    }

    /**
     * Handle Google callback and authenticate the user.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if the user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Create a new user
                $user = User::create([
                    'first_name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(uniqid()), // Random password (not used)
                ]);
            }

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Google login successful',
                'token' => $token,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Google authentication failed', 'details' => $e->getMessage()], 500);
        }
    }

    /** 
     * Date: 28-Mar-2025
     * Update User Data.
     *
     * This method allows updating a user based on the following parameter:
     * - first_name, last_name, email, country
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function update(Request $request, $id)
    {
        if (Auth::user()->role_id !== 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'status_code' => 403,
            ], 403);
        }

        try {
            $user = User::findOrFail($id);
            if ($user) {
                $user->update($request->all());
                return response()->json([
                    'message' => 'User updated successfully!',
                    'user' => $user->fresh(),
                ]);
            } else {
                return response()->json(['error' => 'User not found or could not be updated!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found or could not be updated!', 'message' => $e->getMessage()], 404);
        }
    }

    /** 
     * Date: 1-Apr-2025
     * Change Password.
     *
     * This method changes the password based on the following parameter:
     * - current_password, new_password
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function changePassword(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // If no user is authenticated, return an error
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 403);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);  // Update password on the existing user
        $user->save();  // Save the changes to the existing user in the database

        // Return a success message
        return response()->json(['message' => 'Password changed successfully.']);
    }
}
