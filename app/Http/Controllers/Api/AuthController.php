<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\{LoginRequest, RegisterRequest};
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;




class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function login(LoginRequest $request)
    {
        $type = $request->input('type');

        if ($type === 'google') {
            $response = $this->authService->googleLogin($request->input('provider_token'));
        } elseif ($type === 'email') {
            if ($request->input('type') === 'email') {
                $email = $request->input('email');
                $user = User::where('email', $email)->first();

                if ($user && $user->password === null) {
                    return error('You have previously signed in with Google. Please use Google to log in or reset your password.');
                }
            }
            $response = $this->authService->emailLogin(
                $request->input('email'),
                $request->input('password')
            );
        } else {
            return error("Invalid login type");
        }
        return $response;
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
                'country' => $validated['country'],
                'terms_condition' => $validated['terms_condition'],
                // 'display_name' => $validated['display_name'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                // 'last_login_at' => now(),
                // 'role_id' => 2,  // You can adjust the role as needed
            ]);
            Auth::login($data);
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;
            $fetch = ["name" => $user->first_name, "display_name" => $user->display_name];

            return success("Login successful", ['token' => $token, 'user' => $fetch]);
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
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
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
    /**
     * Send a test email
     */
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
}
