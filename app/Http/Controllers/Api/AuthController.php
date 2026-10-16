<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|max:255|unique:users',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role_id' => 'nullable|exists:roles,id',
                'branch_id' => 'nullable|exists:branches,id',
                'department_id' => 'nullable|exists:departments,id',
                'primary_lead_id' => 'nullable|exists:users,id',
                'secondary_lead_id' => 'nullable|exists:users,id',
                'status' => 'nullable|string|in:active,inactive,suspended',
            ]);

            $user = User::create([
                'username' => $validated['username'],
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'primary_lead_id' => $validated['primary_lead_id'] ?? null,
                'secondary_lead_id' => $validated['secondary_lead_id'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            $tokenString = Str::random(60);
            
            $user->tokens()->create([
                'token' => hash('sha256', $tokenString),
                'ip_address' => $request->ip(),
                'expiry_time' => Carbon::now()->addHours(24),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => $this->formatAuthUser($user),
                'token' => $tokenString,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AuthController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to register user', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'login' => 'sometimes|string',
                'email' => 'sometimes|string',
                'password' => 'required|string',
            ]);

            $loginIdentifier = $request->input('login', $request->input('email'));

            if (!$loginIdentifier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The login or email field is required.',
                ], 422);
            }

            $user = User::where('email', $loginIdentifier)
                ->orWhere('username', $loginIdentifier)
                ->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The provided credentials are incorrect.',
                ], 401);
            }

            $tokenString = Str::random(60);
            
            $user->tokens()->create([
                'token' => hash('sha256', $tokenString),
                'ip_address' => $request->ip(),
                'expiry_time' => Carbon::now()->addHours(24),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in successfully',
                'user' => $this->formatAuthUser($user),
                'token' => $tokenString,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AuthController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to login', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $tokenString = $request->bearerToken();
            if ($tokenString) {
                UserToken::where('token', hash('sha256', $tokenString))->update(['is_revoked' => true]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AuthController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to logout', 'error' => $e->getMessage()], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            return response()->json([
                'status' => 'success',
                'user' => $this->formatAuthUser($request->user())
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AuthController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch user', 'error' => $e->getMessage()], 500);
        }
    }

    public function profile(Request $request)
    {
        return $this->user($request);
    }

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            $tokenString = Str::random(60);

            $user->tokens()->create([
                'token' => hash('sha256', $tokenString),
                'ip_address' => $request->ip(),
                'expiry_time' => Carbon::now()->addHours(24),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'token' => $tokenString,
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AuthController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to refresh token', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Format a clean, lightweight authenticated user payload.
     */
    protected function formatAuthUser(User $user): array
    {
        $user->loadMissing(['role.permissions', 'branch', 'department']);

        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role ? $user->role->name : null,
            'branch' => $user->branch ? $user->branch->name : null,
            'department' => $user->department ? $user->department->name : null,
            'status' => $user->status,
            'permissions' => $user->role && $user->role->permissions
                ? $user->role->permissions->pluck('name')->values()->all()
                : [],
        ];
    }
}
