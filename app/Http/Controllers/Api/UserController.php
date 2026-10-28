<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permissions:view-user', only: ['index', 'show']),
            new Middleware('permissions:create-user', only: ['store']),
            new Middleware('permissions:edit-user', only: ['update', 'assignRole', 'removeRole']),
            new Middleware('permissions:delete-user', only: ['destroy']),
        ];
    }

    /**
     * Helper to get authenticated user and role name.
     */
    protected function getAuthUserAndRole(Request $request): array
    {
        $user = $request->attributes->get('user') ?? $request->user() ?? auth()->user();
        if ($user) {
            $user->loadMissing(['role', 'department']);
        }
        $roleName = strtolower($user?->role?->name ?? $user?->roles?->name ?? '');
        return [$user, $roleName];
    }

    public function index(Request $request)
    {
        try {
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

            $query = User::with(['role.permissions', 'branch', 'department', 'primaryLead', 'secondaryLead']);

            if ($isAdmin) {
                // Admin and superadmin can view all users, or optionally filter by department
                if ($request->filled('department_id')) {
                    $query->where('department_id', $request->department_id);
                }
            } else {
                // Regular user can only see users from their own department
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if ($departmentId !== null) {
                    $query->where('department_id', $departmentId);
                } else {
                    $query->where('id', $authUser?->id);
                }
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('role_id')) {
                $query->where('role_id', $request->role_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            return response()->json([
                'status' => 'success',
                'data' => $query->get()
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve users', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

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

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if (isset($validated['department_id']) && (int)$validated['department_id'] !== (int)$departmentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot create user outside your department'
                    ], 403);
                }
                $validated['department_id'] = $departmentId;
            }

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

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user->load(['role.permissions', 'branch', 'department', 'primaryLead', 'secondaryLead'])
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, User $user)
    {
        try {
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if ($departmentId !== null) {
                    if ((int)$user->department_id !== (int)$departmentId) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Unauthorized to view user outside your department'
                        ], 403);
                    }
                } else {
                    if ((int)$user->id !== (int)$authUser?->id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Unauthorized to view this user'
                        ], 403);
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $user->load(['role.permissions', 'branch', 'department', 'primaryLead', 'secondaryLead'])
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve user', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if ($departmentId !== null && (int)$user->department_id !== (int)$departmentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized to update user outside your department'
                    ], 403);
                }
            }

            $validated = $request->validate([
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'first_name' => 'sometimes|nullable|string|max:255',
                'last_name' => 'sometimes|nullable|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8',
                'role_id' => 'sometimes|nullable|exists:roles,id',
                'branch_id' => 'sometimes|nullable|exists:branches,id',
                'department_id' => 'sometimes|nullable|exists:departments,id',
                'primary_lead_id' => 'sometimes|nullable|exists:users,id',
                'secondary_lead_id' => 'sometimes|nullable|exists:users,id',
                'status' => 'sometimes|string|in:active,inactive,suspended',
            ]);

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if (isset($validated['department_id']) && (int)$validated['department_id'] !== (int)$departmentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot assign user to another department'
                    ], 403);
                }
            }

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user->load(['role.permissions', 'branch', 'department', 'primaryLead', 'secondaryLead'])
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update user', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, User $user)
    {
        try {
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if ($departmentId !== null && (int)$user->department_id !== (int)$departmentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized to delete user outside your department'
                    ], 403);
                }
            }

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
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if ($departmentId !== null && (int)$user->department_id !== (int)$departmentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized to assign role to user outside your department'
                    ], 403);
                }
            }

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
            [$authUser, $roleName] = $this->getAuthUserAndRole($request);
            $isAdmin = in_array($roleName, ['admin', 'superadmin']);

            if (!$isAdmin) {
                $departmentId = $authUser?->department_id ?? $authUser?->departments_id ?? null;
                if ($departmentId !== null && (int)$user->department_id !== (int)$departmentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized to remove role from user outside your department'
                    ], 403);
                }
            }

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

