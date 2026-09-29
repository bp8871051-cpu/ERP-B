<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'This account has been deactivated. Please contact your system administrator.'
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN',
            'module' => 'Authentication',
            'description' => "User {$user->name} logged in via REST API",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $token = $user->createToken('erp-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'company' => $user->company,
                'department' => $user->department,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'LOGOUT',
                'module' => 'Authentication',
                'description' => "User {$request->user()->name} logged out",
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['company', 'department']);
        return response()->json([
            'user' => $user,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CHANGE_PASSWORD',
            'module' => 'Security',
            'description' => "User {$user->name} updated their security password",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function companies(): JsonResponse
    {
        $companies = Company::where('status', 'active')->get();
        return response()->json(['companies' => $companies]);
    }
}
