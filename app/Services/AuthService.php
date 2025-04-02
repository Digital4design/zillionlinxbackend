<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class AuthService
{
    public function emailLogin(string $email, string $password)
    {
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = Auth::user();
            if ($user && $user->role_id == 1) {
                Auth::logout();
                return error("Invalid credentials");
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $data = [
                "name" => $user->first_name . " " . $user->last_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => 'user',
                'email' => $user->email,
                'id' => $user->id,
                'country' => $user->country ?? null,
            ];


            return success("Login successful", ['token' => $token, 'user' => $data]);
        }

        return error("Invalid credentials");
    }

    public function googleLogin(string $googleToken)
    {
        try {
            $user = Socialite::driver('google')->stateless()->userFromToken($googleToken);

            $existingUser = User::where('email', $user->email)->first();
            $data = [];
            if ($existingUser) {

                $token = $existingUser->createToken('auth_token')->plainTextToken;

                $data['authToken'] = $token;
                $data['user'] = $existingUser->first_name . " " . $existingUser->last_name;
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'provider' => 'google',
                    'provider_id' => $user->id,

                    'email_verified_at' => now(),

                ]);
                $token = $newUser->createToken('auth_token')->plainTextToken;
                $data['authToken'] = $token;
                $data['name'] = $newUser->first_name . " " . $newUser->last_name;
                // $data['display_name'] = $newUser->display_name;
            }

            return success("Logged in Successfully!", ['token' => $token, 'user' => $data]);
        } catch (\Exception $e) {
            return error("Invalid token or Google authentication failed.", ["error_msg" => $e->getMessage()]);
        }
    }
}
