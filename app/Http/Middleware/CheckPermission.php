<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Super Admin and Admin bypass all checks
        if ($user->role === 'Super Admin' || $user->role === 'Admin') {
            return $next($request);
        }

        // Check if user has the requested permission
        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => "Access denied. You do not possess the required permission: '{$permission}' to perform this action.",
            'required_permission' => $permission,
            'user_role' => $user->role,
        ], 403);
    }
}
