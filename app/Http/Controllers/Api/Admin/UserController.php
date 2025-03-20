<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\{LoginRequest, RegisterRequest};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function index(Request $request)
    {
        try {

            $query = User::where('role_id', 2);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(10);

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
    * Delete User Data.
    *
    * This method allows deleting a user based on the following parameter:
    * - ID
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function destroy($id)
    {
        if (Auth::user()->role_id !== 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'status_code' => 403,
            ], 403);
        }
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'User deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found or could not be deleted!', 'message' => $e->getMessage()], 404);
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
        if (Auth::user()->role_id !== 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'status_code' => 403,
            ], 403);
        }
        try {
            $user = User::findOrFail($id);
            $user->update($request->all());
            return response()->json(['message' => 'User updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found or could not be updated!', 'message' => $e->getMessage()], 404);
        }
    }
}
