<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => Branch::withCount('users')->get()
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BranchController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch branches', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:branches,name',
            ]);

            $branch = Branch::create(['name' => $validated['name']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Branch created successfully',
                'data' => $branch
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BranchController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create branch', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Branch $branch)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $branch->load('users')
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BranchController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve branch', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Branch $branch)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
            ]);

            $branch->update(['name' => $validated['name']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Branch updated successfully',
                'data' => $branch
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BranchController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update branch', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Branch $branch)
    {
        try {
            $branch->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Branch deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BranchController Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete branch', 'error' => $e->getMessage()], 500);
        }
    }
}
