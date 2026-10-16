<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Requests\BulkUpdateDomainRequest;
use App\Http\Resources\DomainResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DomainController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permissions:view-domain', only: ['index', 'show']),
            new Middleware('permissions:create-domain', only: ['store']),
            new Middleware('permissions:edit-domain', only: ['update', 'bulkUpdate']),
            new Middleware('permissions:delete-domain', only: ['destroy']),
        ];
    }

    /**
     * Helper to get authenticated user and role.
     */
    protected function getAuthUserAndRole(Request $request): array
    {
        $user = $request->attributes->get('user') ?? $request->user();
        $roleName = strtolower($user?->role?->name ?? $user?->roles?->name ?? '');
        return [$user, $roleName];
    }

    /**
     * Display a listing of domains scoped by role.
     */
    public function index(Request $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);

        $query = Domain::with(['branch', 'salesPerson', 'renewedBy', 'payments']);

        // Scope by user role
        if (in_array($role, ['admin', 'superadmin', 'accounts'])) {
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('sales_person_id')) {
                $query->where('sales_person_id', $request->sales_person_id);
            }
        } elseif ($role === 'manager') {
            $query->where('branch_id', $user->branch_id);
            if ($request->filled('sales_person_id')) {
                $query->where('sales_person_id', $request->sales_person_id);
            }
        } elseif ($role === 'employee') {
            $query->where('sales_person_id', $user->id);
        }

        // Filters
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('system_status')) {
            $query->where('system_status', $request->system_status);
        }

        if ($request->filled('manual_status')) {
            $query->where('manual_status', $request->manual_status);
        }

        $perPage = (int)$request->get('per_page', $request->get('limit', 15));
        $domains = $query->orderBy('expiry_date', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DomainResource::collection($domains),
            'meta' => [
                'current_page' => $domains->currentPage(),
                'last_page' => $domains->lastPage(),
                'per_page' => $domains->perPage(),
                'total' => $domains->total(),
            ]
        ], 200);
    }

    /**
     * Store a newly created domain.
     */
    public function store(StoreDomainRequest $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $data = $request->validated();

        if ($role === 'employee') {
            $data['sales_person_id'] = $user->id;
            $data['branch_id'] = $user->branch_id;
        } elseif ($role === 'manager') {
            $data['branch_id'] = $user->branch_id;
            $data['sales_person_id'] = $data['sales_person_id'] ?? $user->id;
        } else {
            $data['branch_id'] = $data['branch_id'] ?? $user?->branch_id;
            $data['sales_person_id'] = $data['sales_person_id'] ?? $user?->id;
        }

        $domain = Domain::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Domain created successfully',
            'data' => new DomainResource($domain->load(['branch', 'salesPerson', 'renewedBy', 'payments']))
        ], 201);
    }

    /**
     * Display the specified domain.
     */
    public function show(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $domain = Domain::with(['branch', 'salesPerson', 'renewedBy', 'payments.status', 'activityLogs.user'])->findOrFail($id);

        if (!$this->canAccessDomain($user, $role, $domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this domain.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new DomainResource($domain)
        ], 200);
    }

    /**
     * Update the specified domain.
     */
    public function update(UpdateDomainRequest $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $domain = Domain::findOrFail($id);

        if (!$this->canAccessDomain($user, $role, $domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this domain.'
            ], 403);
        }

        $data = $request->validated();

        // Enforce boundaries for non-admin
        if ($role === 'employee') {
            unset($data['sales_person_id'], $data['branch_id']);
        } elseif ($role === 'manager') {
            unset($data['branch_id']);
        }

        // Enforce payment-before-renewal logic from Domain project
        if (isset($data['expiry_date']) && $data['expiry_date'] > $domain->expiry_date) {
            $domain->load('payments');
            $totalPaid = $domain->payments()->sum('amount');
            $balancePending = $domain->total_amount - $totalPaid;

            if ($balancePending > 0 && !$request->boolean('override_payment_check')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot renew: domain has a pending balance of ' . number_format($balancePending, 2) . '. Pass override_payment_check=true to force.',
                    'balance_pending' => $balancePending,
                ], 422);
            }
        }

        $domain->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Domain updated successfully',
            'data' => new DomainResource($domain->load(['branch', 'salesPerson', 'renewedBy', 'payments']))
        ], 200);
    }

    /**
     * Delete the specified domain.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $domain = Domain::findOrFail($id);

        if (!in_array($role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete domains.'
            ], 403);
        }

        $domain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Domain deleted successfully'
        ], 200);
    }

    /**
     * Bulk update domains.
     */
    public function bulkUpdate(BulkUpdateDomainRequest $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $domainIds = $request->domain_ids;

        $query = Domain::whereIn('id', $domainIds);

        if ($role === 'manager') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($role === 'employee') {
            $query->where('sales_person_id', $user->id);
        }

        $domainsToUpdate = $query->get();

        if ($domainsToUpdate->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No eligible domains found for update.'
            ], 404);
        }

        $updateData = array_filter([
            'sales_person_id'  => $request->sales_person_id,
            'manual_status'    => $request->manual_status,
            'branch_id'        => in_array($role, ['admin', 'superadmin']) ? $request->branch_id : null,
            'next_followup_at' => $request->next_followup_at,
            'remark'           => $request->remark,
        ], fn($val) => !is_null($val));

        $count = 0;
        foreach ($domainsToUpdate as $domain) {
            $domain->update($updateData);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$count} domain(s)."
        ], 200);
    }

    /**
     * Check if user can access the domain.
     */
    protected function canAccessDomain($user, string $role, Domain $domain): bool
    {
        if (in_array($role, ['admin', 'superadmin', 'accounts'])) {
            return true;
        }

        if ($role === 'manager') {
            return (int)$domain->branch_id === (int)$user?->branch_id;
        }

        if ($role === 'employee') {
            return (int)$domain->sales_person_id === (int)$user?->id;
        }

        return false;
    }
}
