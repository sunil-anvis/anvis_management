<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\AccountsExport;
use App\Models\Accounts;
use App\Models\RoForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use Maatwebsite\Excel\Facades\Excel;

class AccountsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permissions:view-accounts', only: ['index', 'show', 'export']),
            new Middleware('permissions:manage-accounts', only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * Authorize that the user belongs to Accounts Department (or is Admin)
     */
    private function authorizeAccountsDepartment(Request $request)
    {
        $user = $request->attributes->get('user');


        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $userRole = strtolower($user->roles?->name ?? '');

        // Admin role bypasses department restriction
        if ($userRole === 'admin') {
            return null;
        }

        $userDept = strtolower($user->departments?->name ?? '');

        if ($userDept !== 'accounts') {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Only Accounts department personnel are authorized.'
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $accounts = Accounts::with('roForm')
            ->whereHas('roForm', function ($query) {
                $query->where('status', 'approved');
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Successfully fetched approved forms',
            'data' => $accounts
        ], 200);
    }

    public function store(Request $request)
    {
        if ($authError = $this->authorizeAccountsDepartment($request)) {
            return $authError;
        }

        $valid = Validator::make($request->all(), [
            'ro_form_id' => 'required|integer|exists:ro_forms,id',
            'bill_name' => 'required|string|max:200',
            'anvis_invoice' => 'required|string|max:255',
            'anvis_invoice_date' => 'required|date',
            'anvis_status' => 'required|string|max:255',
            'vendor_invoice' => 'required|string|max:200',
            'vendor_invoice_date' => 'required|date',
            'vendor_status' => 'required|string|max:200',
            'external_amount' => 'required|numeric',
            'executive' => 'required|string|max:200',
            'comments' => 'nullable|string',
            'comment' => 'nullable|string'
        ]);

        if ($valid->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'error' => $valid->errors()
            ], 422);
        }

        $data = $valid->validated();
        if (isset($data['comment']) && !isset($data['comments'])) {
            $data['comments'] = $data['comment'];
        }
        unset($data['comment']);

        $accounts = Accounts::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Successfully created',
            'data' => $accounts
        ], 201);
    }

    public function show(Request $request, $id)
    {
        if ($authError = $this->authorizeAccountsDepartment($request)) {
            return $authError;
        }

        $accounts = Accounts::with('roForm')->find($id);

        if (!$accounts) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully fetched',
            'data' => $accounts
        ], 200);
    }

    public function update(Request $request, $id)
    {
        if ($authError = $this->authorizeAccountsDepartment($request)) {
            return $authError;
        }

        $accounts = Accounts::find($id);

        if (!$accounts) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        $valid = Validator::make($request->all(), [
            'ro_form_id' => 'sometimes|integer|exists:ro_forms,id',
            'bill_name' => 'sometimes|string|max:200',
            'anvis_invoice' => 'sometimes|string|max:255',
            'anvis_invoice_date' => 'sometimes|date',
            'anvis_status' => 'sometimes|string|max:255',
            'vendor_invoice' => 'sometimes|string|max:200',
            'vendor_invoice_date' => 'sometimes|date',
            'vendor_status' => 'sometimes|string|max:200',
            'external_amount' => 'sometimes|numeric',
            'executive' => 'sometimes|string|max:200',
            'comments' => 'nullable|string',
            'comment' => 'nullable|string'
        ]);

        if ($valid->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'error' => $valid->errors()
            ], 422);
        }

        $data = $valid->validated();
        if (array_key_exists('comment', $data) && !array_key_exists('comments', $data)) {
            $data['comments'] = $data['comment'];
        }
        unset($data['comment']);

        $accounts->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
            'data' => $accounts
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        if ($authError = $this->authorizeAccountsDepartment($request)) {
            return $authError;
        }

        $accounts = Accounts::find($id);

        if (!$accounts) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        $accounts->delete();

        return response()->json([
            'success' => true,
            'message' => 'Accounts deleted successfully'
        ], 200);
    }

    public function export(Request $request)
    {
        if ($authError = $this->authorizeAccountsDepartment($request)) {
            return $authError;
        }

        $valid = Validator::make($request->all(), [
            'from_date' => 'nullable|date|date_format:Y-m-d',
            'to_date'   => 'nullable|date|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date filter',
                'errors'  => $valid->errors(),
            ], 422);
        }

        return Excel::download(
            new AccountsExport(
                $request->from_date,
                $request->to_date
            ),
            'approved_ro' . now()->format('Y_m_d_H_i_s') . '.xlsx'
        );
    }
}
