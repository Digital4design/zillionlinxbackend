<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Category;
use App\Models\Bookmark;
use App\Models\UserBookmark;
use App\Http\Requests\Api\{LoginRequest, RegisterRequest};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function __construct()
    {
        if (Auth::check() && Auth::user()->role_id !== 1) {
            abort(response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'status_code' => 403,
            ], 403));
        }
    }

    public function index(Request $request)
    {

        try {
            $query = User::where('role_id', 2)
                ->withCount('totalBookmarks');

            // Apply search
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Acceptable sort fields and map them
            $sortMap = [
                'name' => 'first_name',
                'country' => 'country',
                'created' => 'created_at',
                'last_access' => 'last_login_at',
                'total_bookmarks' => 'total_bookmarks_count',
            ];

            // Get sort parameters from request
            if (!$request->input('sort_by')) {
                $sortByInput = $request->input('sort_by', 'created');
            } else {
                $sortByInput = $request->input('sort_by');
            }

            if (!$request->input('sort_order')) {

                $sortOrder = $request->input('sort_order', 'desc');
            } else {
                $sortOrder = $request->input('sort_order');
            }



            // Normalize sort field
            $sortBy = $sortMap[$sortByInput] ?? 'created_at';

            // Apply sorting logic
            if ($sortByInput === 'name') {
                $query->orderBy('first_name', $sortOrder)->orderBy('last_name', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Paginate results
            $users = $query->paginate(2);

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No users found',
                    'status_code' => 200,
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong. Please try again later.',
                'status' => 'error',
                'status_code' => 500,
            ], 500);
        }
    }


    /*
    * Date: 18-Mar-2025
    * Create User Data.
    *
    * This method allows creating a user based on the following parameter:
    * - first_name, last_name, email, password, country
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function create(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            if (Auth::user()->role_id !== 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'status_code' => 403,
                ], 403);
            }

            // Create a new user
            $data = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'country' => $request->input('country'),
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            return success("Registered successful");
        } catch (\Exception $ex) {
            return error($ex->getMessage());
        }
    }

    /*
    * Date: 20-Mar-2025
    * Updated: 04-Apr-2025
    
    * This method can delete multiple users at once if an array of IDs is provided.
    * If a single ID is provided, it will delete that user.
    * When user is deleted it will also delete all the bookmarks and categories associated with that user.
    *   
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function destroy(Request $request)
    {
        try {
            $ids = $request->input('ids'); // Expecting an array or a single ID
            $ids = is_array($ids) ? $ids : [$ids];
            if (is_array($ids)) {

                $user = User::whereIn('id', $ids)->get();
                // dd($user);
                if ($user->isNotEmpty()) {

                    User::whereIn('id', $ids)->delete();
                    Category::whereIn('user_id', $ids)->delete();
                    Bookmark::whereIn('user_id', $ids)->delete();
                    UserBookmark::whereIn('user_id', $ids)->delete();

                    return response()->json(['message' => 'User(s) deleted successfully!']);
                } else {
                    return response()->json(['error' => 'User(s) not found or could not be deleted!'], 404);
                }
            } else {
                return response()->json(['error' => 'Something went wrong!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred!', 'message' => $e->getMessage()], 500);
        }
    }

    /*
    * Date: 20-Mar-2025
    * Update User Data.
    *
    * This method allows updating a user based on the following parameter:
    * - first_name, last_name, email, country
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update($request->all());
            return response()->json([
                'message' => 'User updated successfully!',
                'user' => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found or could not be updated!', 'message' => $e->getMessage()], 404);
        }
    }
}
