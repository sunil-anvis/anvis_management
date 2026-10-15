<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => Permission::all()
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PermissionController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch permissions', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:permissions,name',
            ]);

            $permission = Permission::create(['name' => $validated['name']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PermissionController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create permission', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Permission $permission)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $permission
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PermissionController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch permission', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Permission $permission)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:permissions,name,' . $permission->id,
            ]);

            $permission->update(['name' => $validated['name']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission updated successfully',
                'data' => $permission
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PermissionController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update permission', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            $permission->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Permission deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete permission', 'error' => $e->getMessage()], 500);
        }
    }
}
