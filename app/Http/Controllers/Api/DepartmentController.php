<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => Department::withCount('users')->get()
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DepartmentController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch departments', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:departments,name',
            ]);

            $department = Department::create(['name' => $validated['name']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Department created successfully',
                'data' => $department
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DepartmentController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create department', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Department $department)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $department->load('users')
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DepartmentController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve department', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Department $department)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            ]);

            $department->update(['name' => $validated['name']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Department updated successfully',
                'data' => $department
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DepartmentController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update department', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Department $department)
    {
        try {
            $department->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Department deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DepartmentController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete department', 'error' => $e->getMessage()], 500);
        }
    }
}
