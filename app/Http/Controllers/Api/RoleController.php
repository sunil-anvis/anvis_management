<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => Role::with('permissions')->get()
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RoleController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch roles', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name',
                'permissions' => 'sometimes|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $role = Role::create(['name' => $validated['name']]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => $role->load('permissions')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RoleController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create role', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Role $role)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $role->load('permissions')
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RoleController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch role', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Role $role)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|unique:roles,name,' . $role->id,
                'permissions' => 'sometimes|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            if (isset($validated['name'])) {
                $role->update(['name' => $validated['name']]);
            }

            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RoleController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update role', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Role $role)
    {
        try {
            $role->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Role deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete role', 'error' => $e->getMessage()], 500);
        }
    }
}
