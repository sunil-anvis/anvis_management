<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoForm;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('user') ?? $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $roleName = strtolower(
            $user->role?->name ?? $user->roles?->name ?? ''
        );

        $departmentId = $user->department_id ?? $user->departments_id ?? null;

        switch ($roleName) {
            // Admin can see ALL departments
            case 'admin':
            case 'superadmin':
                return $this->dashboard();

            case 'manager':
            case 'lead':
            default:
                return $this->dashboard($departmentId);
        }
    }

    /**
     * Dashboard
     *
     * @param int|null $departmentId
     */
    private function dashboard($departmentId = null)
    {
        $query = RoForm::query();

        if ($departmentId !== null) {
            $query->where('departments_id', $departmentId);
        }

        $managersCount = $departmentId !== null
            ? User::where(function ($q) {
                $q->whereHas('role', fn ($r) => $r->whereIn('name', ['manager', 'lead']))
                  ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['manager', 'lead']));
            })->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhere('departments_id', $departmentId);
            })->count()
            : User::where(function ($q) {
                $q->whereHas('role', fn ($r) => $r->whereIn('name', ['manager', 'lead']))
                  ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['manager', 'lead']));
            })->count();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard fetched successfully',
            'data' => [
                'statistics' => [
                    'total_ro_form' => (clone $query)->count(),

                    'pending_ro_form' => (clone $query)
                        ->where('status', 'pending')
                        ->count(),

                    'approved_ro_form' => (clone $query)
                        ->where('status', 'approved')
                        ->count(),

                    'rejected_ro_form' => (clone $query)
                        ->where('status', 'rejected')
                        ->count(),

                    'cancelled_ro_form' => (clone $query)
                        ->where('status', 'cancelled')
                        ->count(),

                    'deleted_ro_form' => (clone $query)
                        ->where('status', 'deleted')
                        ->count(),

                    'total_vendor' => Vendor::count(),

                    'total_user' => $departmentId !== null
                        ? User::where(function ($q) use ($departmentId) {
                            $q->where('department_id', $departmentId)
                              ->orWhere('departments_id', $departmentId);
                        })->count()
                        : User::count(),

                    'total_managers' => $managersCount,
                    'total_leads' => $managersCount, // Kept for frontend backwards compatibility
                ],
            ],
        ]);
    }
}
