<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Bookmark;
use App\Models\UserBookmark;
use Carbon\Carbon;

class AuthService
{
    public function emailLogin(string $email, string $password, bool $remember_me)
    {
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = Auth::user();
            if ($user && $user->role_id == 1) {
                Auth::logout();
                return error("Invalid credentials");
            }

            // $token = $user->createToken('auth_token')->plainTextToken;
            $token = $user->createToken('auth_token')->plainTextToken;

            $accessToken = $user->tokens()->latest()->first();
            $accessToken->expires_at = $remember_me
                ? Carbon::now()->addDays(10)
                : Carbon::now()->addDays(1);
            $accessToken->save();


            $data = [
                "name" => $user->first_name . " " . $user->last_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => 'user',
                'email' => $user->email,
                'id' => $user->id,
                'google_id' => $user->google_id,
                'country' => $user->country ?? null,
            ];


            return success("Login successful", ['token' => $token, 'user' => $data, 'expires_at' => $accessToken->expires_at]);
        }

        return error("Invalid credentials");
    }


    public function googleLogin(string $googleToken)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($googleToken);

            $emailUser =  User::where('email', $googleUser->email)->whereNull('google_id')->first();
            if ($emailUser) {
                return response()->json([
                    'message' => 'Email already exists with different provider'
                ], 404);
            }
            $fullName = explode(' ', $googleUser->getName, 2);
            $firstName = $fullName[0] ?? null;
            $lastName = $fullName[1] ?? null;

            $existingUser = User::where('email', $googleUser->email)->first();
            if ($existingUser) {
                // Update missing fields if they don't exist
                if (!$existingUser->google_id) $existingUser->google_id = $googleUser->id;
                if (!$existingUser->first_name) $existingUser->first_name = $firstName;
                if (!$existingUser->last_name) $existingUser->last_name = $lastName;
                if (!$existingUser->country) $existingUser->country = 'Unknown'; // You may want to get this from Google if available
                $existingUser->save();

                $token = $existingUser->createToken('auth_token')->plainTextToken;
                return success("Logged in Successfully!", [
                    'token' => $token,
                    'user' => [
                        'id' => $existingUser->id,
                        'google_id' => $existingUser->google_id,
                        'first_name' => $existingUser->first_name,
                        'last_name' => $existingUser->last_name,
                        'country' => $existingUser->country,
                        // 'authToken' => $token,
                        'role' => "user",
                        'email' => $existingUser->email,
                    ]
                ]);
            } else {
                // Create a new user
                $newUser = User::create([

                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'provider' => 'google',
                    'terms_condition' => true,
                    'email_verified_at' => now(),
                    'country' => $googleUser->country ?? NULL,
                ]);
                // Create default bookmarks for the new user
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
                        'user_id' => $newUser->id,

                    ]);
                    UserBookmark::create([
                        'bookmark_id' => $bookmarkCreated->id,
                        'category_id' => $getData->category_id,
                        'sub_category_id' => $getData->sub_category_id,
                        'add_to' => $getData->add_to,
                        'pinned' => $getData->pinned,
                        'user_id' => $newUser->id,
                    ]);
                }

                $token = $newUser->createToken('auth_token')->plainTextToken;
                return success("Logged in Successfully!", [
                    'token' => $token,
                    'user' => [
                        'id' => $newUser->id,
                        'google_id' => $newUser->google_id,
                        'first_name' => $newUser->first_name,
                        'last_name' => $newUser->last_name,
                        'country' => $newUser->country,
                        // 'authToken' => $token,
                        'role' => "user",
                        'user' => $newUser->name,
                        'email' => $newUser->email,
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return error("Invalid token or Google authentication failed.", ["error_msg" => $e->getMessage()]);
        }
    }
}
