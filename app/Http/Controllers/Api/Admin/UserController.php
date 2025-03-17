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
}
