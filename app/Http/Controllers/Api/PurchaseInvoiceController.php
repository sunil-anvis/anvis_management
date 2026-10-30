<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Http\Resources\PurchaseInvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseInvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permissions:view-purchase-invoice', only: ['index', 'show', 'downloadFile']),
            new Middleware('permissions:create-purchase-invoice', only: ['store']),
            new Middleware('permissions:edit-purchase-invoice', only: ['update']),
            new Middleware('permissions:delete-purchase-invoice', only: ['destroy']),
        ];
    }

    protected function getAuthUserAndRole(Request $request): array
    {
        $user = $request->attributes->get('user') ?? $request->user();
        $roleName = strtolower($user?->role?->name ?? $user?->roles?->name ?? '');
        return [$user, $roleName];
    }

    /**
     * Display a listing of purchase invoices.
     */
    public function index(Request $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);

        // Branch-based restriction from Domain project: Mumbai branch only for non-admins
        $userBranch = $user?->branch;
        if (!in_array($role, ['admin', 'superadmin']) && (!$userBranch || strtolower($userBranch->name) !== 'mumbai')) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase invoices can only be accessed by users in the Mumbai branch.'
            ], 403);
        }

        $query = PurchaseInvoice::with('user');

        if (in_array($role, ['admin', 'superadmin', 'accounts'])) {
            // View all
        } else {
            $query->where('user_id', $user?->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        $perPage = (int)$request->get('per_page', $request->get('limit', 15));
        $invoices = $query->orderBy('payment_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PurchaseInvoiceResource::collection($invoices),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        ], 200);
    }

    /**
     * Store a newly created purchase invoice.
     */
    public function store(Request $request): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);

        // Branch-based restriction from Domain project: Mumbai branch only for non-admins
        $userBranch = $user?->branch;
        if (!in_array($role, ['admin', 'superadmin']) && (!$userBranch || strtolower($userBranch->name) !== 'mumbai')) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase invoices can only be managed by users in the Mumbai branch.'
            ], 403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'company_name' => 'required|string|max:255',
            'status' => 'sometimes|string|max:50',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('invoice_file')) {
            $filePath = $request->file('invoice_file')->store('purchase_invoices', 'public');
        }

        $invoice = PurchaseInvoice::create([
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'company_name' => $data['company_name'],
            'status' => $data['status'] ?? 'Pending',
            'invoice_file_path' => $filePath,
            'user_id' => $user?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase invoice recorded successfully',
            'data' => new PurchaseInvoiceResource($invoice->load('user'))
        ], 201);
    }

    /**
     * Display the specified purchase invoice.
     */
    public function show(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $invoice = PurchaseInvoice::with('user')->findOrFail($id);

        if (!in_array($role, ['admin', 'superadmin', 'accounts']) && (int)$invoice->user_id !== (int)$user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this purchase invoice.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new PurchaseInvoiceResource($invoice)
        ], 200);
    }

    /**
     * Update the specified purchase invoice.
     */
    public function update(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $invoice = PurchaseInvoice::findOrFail($id);

        if (!in_array($role, ['admin', 'superadmin', 'accounts']) && (int)$invoice->user_id !== (int)$user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this purchase invoice.'
            ], 403);
        }

        $data = $request->validate([
            'amount' => 'sometimes|required|numeric|min:0.01',
            'payment_date' => 'sometimes|required|date',
            'company_name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|string|max:50',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('invoice_file')) {
            if ($invoice->invoice_file_path && Storage::disk('public')->exists($invoice->invoice_file_path)) {
                Storage::disk('public')->delete($invoice->invoice_file_path);
            }
            $data['invoice_file_path'] = $request->file('invoice_file')->store('purchase_invoices', 'public');
        }

        unset($data['invoice_file']);
        $invoice->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Purchase invoice updated successfully',
            'data' => new PurchaseInvoiceResource($invoice->load('user'))
        ], 200);
    }

    /**
     * Remove the specified purchase invoice.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $invoice = PurchaseInvoice::findOrFail($id);

        if (!in_array($role, ['admin', 'superadmin']) && (int)$invoice->user_id !== (int)$user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this purchase invoice.'
            ], 403);
        }

        if ($invoice->invoice_file_path && Storage::disk('public')->exists($invoice->invoice_file_path)) {
            Storage::disk('public')->delete($invoice->invoice_file_path);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase invoice deleted successfully'
        ], 200);
    }

    /**
     * Download or stream the purchase invoice file.
     */
    public function downloadFile(Request $request, $id)
    {
        [$user, $role] = $this->getAuthUserAndRole($request);
        $invoice = PurchaseInvoice::findOrFail($id);

        if (!in_array($role, ['admin', 'superadmin', 'accounts']) && (int)$invoice->user_id !== (int)$user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access this purchase invoice file.'
            ], 403);
        }

        if (!$invoice->invoice_file_path) {
            return response()->json([
                'success' => false,
                'message' => 'No file attached to this purchase invoice.'
            ], 404);
        }

        $path = $invoice->invoice_file_path;

        // 1. If not found on local public disk, attempt recovery
        if (!Storage::disk('public')->exists($path)) {
            // Check sibling directories on same hosting server
            $candidates = [
                base_path("../domain/storage/app/public/{$path}"),
                base_path("../../domain/storage/app/public/{$path}"),
                base_path("../clients/domain/storage/app/public/{$path}"),
                base_path("../../clients/domain/storage/app/public/{$path}"),
                base_path("../../../clients/domain/storage/app/public/{$path}"),
                storage_path("app/{$path}"),
                public_path("storage/{$path}"),
                public_path("public_storage/{$path}"),
            ];

            $foundCandidate = false;
            foreach ($candidates as $candidate) {
                if (file_exists($candidate) && is_file($candidate)) {
                    Storage::disk('public')->put($path, file_get_contents($candidate));
                    $foundCandidate = true;
                    break;
                }
            }

            // Fallback: restore from legacy domain server if still missing
            if (!$foundCandidate) {
                $legacyUrl = "https://anvisinfotech.com/clients/domain/public_storage/{$path}";
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($legacyUrl);
                    if ($response->successful()) {
                        Storage::disk('public')->put($path, $response->body());
                    }
                } catch (\Throwable $e) {
                    // Legacy host unreachable
                }
            }
        }

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice file does not exist on the server.'
            ], 404);
        }

        return Storage::disk('public')->response($path, null, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
        ]);
    }
}
