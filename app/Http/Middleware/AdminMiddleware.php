<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('AdminMiddleware called', ['user' => Auth::user()]);

        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 'error',
                'status_code' => 401,
            ], 401);
        }

        // Check if user is an admin (role = 1)
        if (Auth::user()->role == 1) {
            Log::warning('Unauthorized access - User does not have admin role.', ['role' => Auth::user()->role]);
            return response()->json([

                'message' => 'Access Denied. Admins only.',
                'status' => 'error',
                'status_code' => 403,
                // 'role' => Auth::user()->role
            ], 403);
        }

        return $next($request);
    }
}
