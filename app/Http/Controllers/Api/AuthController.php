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
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
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
                'user' => $user,
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
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $validated['email'])->first();

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
                'user' => $user,
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
                'user' => $request->user()->load(['role.permissions'])
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AuthController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch user', 'error' => $e->getMessage()], 500);
        }
    }
}
