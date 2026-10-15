<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => User::with('role.permissions')->get()
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve users', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role_id' => 'nullable|exists:roles,id',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user->load('role.permissions')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $user->load('role.permissions')
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve user', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8',
                'role_id' => 'sometimes|nullable|exists:roles,id',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user->load('role.permissions')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update user', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete user', 'error' => $e->getMessage()], 500);
        }
    }

    public function assignRole(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|exists:roles,id',
            ]);

            $user->update(['role_id' => $validated['role_id']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Role assigned successfully',
                'data' => $user->load('role')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to assign role', 'error' => $e->getMessage()], 500);
        }
    }

    public function removeRole(Request $request, User $user)
    {
        try {
            $user->update(['role_id' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Role removed successfully',
                'data' => $user->load('role')
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to remove role', 'error' => $e->getMessage()], 500);
        }
    }
}
