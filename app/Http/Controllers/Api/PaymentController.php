<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Domain;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PaymentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permissions:view-payment', only: ['index', 'show']),
            new Middleware('permissions:create-payment', only: ['store']),
            new Middleware('permissions:edit-payment', only: ['update']),
            new Middleware('permissions:delete-payment', only: ['destroy']),
        ];
    }

    protected function getAuthUserAndRole(Request $request): array
    {
        $user = $request->attributes->get('user') ?? $request->user();
        $roleName = strtolower($user?->role?->name ?? $user?->roles?->name ?? '');
        return [$user, $roleName];
    }

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);

        $query = Payment::with(['domain', 'status']);

        if ($request->filled('domain_id')) {
            $query->where('domain_id', $request->domain_id);
        }

        if (in_array($role, ['admin', 'superadmin', 'accounts'])) {
            // Can see all payments
        } elseif ($role === 'manager') {
            $query->whereHas('domain', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        } elseif ($role === 'employee') {
            $query->whereHas('domain', function ($q) use ($user) {
                $q->where('sales_person_id', $user->id);
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments)
        ], 200);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);

        $data = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_status_id' => 'required|exists:payment_statuses,id',
        ]);

        $domain = Domain::findOrFail($data['domain_id']);

        if (!$this->canManageDomain($user, $role, $domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to add payments for this domain.'
            ], 403);
        }

        $payment = Payment::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => new PaymentResource($payment->load(['domain', 'status']))
        ], 201);
    }

    /**
     * Display the specified payment.
     */
    public function show(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $payment = Payment::with(['domain', 'status'])->findOrFail($id);

        if (!$this->canManageDomain($user, $role, $payment->domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this payment.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment)
        ], 200);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $payment = Payment::with('domain')->findOrFail($id);

        if (!$this->canManageDomain($user, $role, $payment->domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this payment.'
            ], 403);
        }

        $data = $request->validate([
            'domain_id' => 'sometimes|required|exists:domains,id',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'payment_date' => 'sometimes|required|date',
            'payment_status_id' => 'sometimes|required|exists:payment_statuses,id',
        ]);

        if (isset($data['domain_id']) && (int)$data['domain_id'] !== (int)$payment->domain_id) {
            $newDomain = Domain::findOrFail($data['domain_id']);
            if (!$this->canManageDomain($user, $role, $newDomain)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reassign payment to the selected domain.'
                ], 403);
            }
        }

        $payment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => new PaymentResource($payment->load(['domain', 'status']))
        ], 200);
    }

    /**
     * Delete the specified payment.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $payment = Payment::with('domain')->findOrFail($id);

        if (!$this->canManageDomain($user, $role, $payment->domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this payment.'
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully'
        ], 200);
    }

    protected function canManageDomain($user, string $role, Domain $domain): bool
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
