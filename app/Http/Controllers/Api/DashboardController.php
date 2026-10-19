<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Domain;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\RoForm;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a merged dashboard with both RO Management and Domain Management statistics.
     * Permissions & Department Scoping:
     * - Admin / Superadmin: Full visibility across all departments and modules.
     * - Managers / Employees: Department-scoped, filtered strictly by user permissions:
     *   - 'view-ro-form': Controls visibility of RO Form details.
     *   - 'view-ro-revenue': Controls visibility of RO revenue & profit/loss financials.
     *   - 'view-domain': Controls visibility of Domain Management details.
     *   - 'view-purchase-invoice': Controls visibility of Purchase Invoices details.
     *   - 'view-user': Controls visibility of team/user statistics.
     *   - 'view-vendor': Controls visibility of vendor counts.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user') ?? $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $user->loadMissing(['role', 'branch', 'department']);

        $roleName = strtolower(
            $user->role?->name ?? $user->roles?->name ?? ''
        );

        $isAdmin = in_array($roleName, ['admin', 'superadmin']);
        $isAccounts = ($roleName === 'accounts');

        // Permission checks
        $canViewRo = $user->hasPermission('view-ro-form');
        $canViewRoRevenue = $user->hasPermission('view-ro-revenue');
        $canViewDomain = $user->hasPermission('view-domain');
        $canViewPurchaseInvoice = $user->hasPermission('view-purchase-invoice');
        $canViewUser = $user->hasPermission('view-user') || in_array($roleName, ['admin', 'superadmin', 'manager', 'lead']);
        $canViewVendor = $user->hasPermission('view-vendor') || in_array($roleName, ['admin', 'superadmin', 'manager', 'lead']);

        // Department & Branch scoping
        if ($isAdmin) {
            $departmentId = $request->filled('department_id') ? (int)$request->department_id : null;
            $branchId = $request->filled('branch_id') ? (int)$request->branch_id : null;
        } else {
            $departmentId = $user->department_id ?? $user->departments_id ?? null;
            $branchId = $user->branch_id ?? null;
        }

        $branchName = $branchId
            ? (Branch::find($branchId)?->name ?? $user->branch?->name)
            : ($user->branch?->name ?? null);

        $departmentName = $departmentId
            ? (Department::find($departmentId)?->name ?? $user->department?->name)
            : ($user->department?->name ?? null);

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $data = [
            'role' => $roleName,
            'branch' => $branchName,
            'department' => $departmentName,
        ];

        // ---------------------------------------------------------
        // 1. Users / Team Statistics (Permission: view-user)
        // ---------------------------------------------------------
        if ($canViewUser) {
            $userQuery = User::query();

            if ($departmentId !== null) {
                $userQuery->where('department_id', $departmentId);
            }

            $totalUsers = (clone $userQuery)->count();

            $managersCount = (clone $userQuery)->where(function ($q) {
                $q->whereHas('role', fn($r) => $r->whereIn('name', ['manager', 'lead']))
                  ->orWhereHas('roles', fn($r) => $r->whereIn('name', ['manager', 'lead']));
            })->count();

            $employeesCount = (clone $userQuery)->where(function ($q) {
                $q->whereHas('role', fn($r) => $r->where('name', 'employee'))
                  ->orWhereHas('roles', fn($r) => $r->where('name', 'employee'));
            })->count();

            $data['users'] = [
                'total' => $totalUsers,
                'managers' => $managersCount,
                'employees' => $employeesCount,
            ];
        }

        // ---------------------------------------------------------
        // 2. RO Management Statistics (Permission: view-ro-form)
        // ---------------------------------------------------------
        if ($canViewRo) {
            $roQuery = RoForm::query();

            if ($departmentId !== null) {
                $roQuery->where('departments_id', $departmentId);
            } elseif ($isAccounts) {
                $roQuery->where('status', 'approved');
            }

            if ($fromDate) {
                $roQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $roQuery->whereDate('created_at', '<=', $toDate);
            }

            $totalRo = (clone $roQuery)->count();
            $pendingRo = (clone $roQuery)->where('status', 'pending')->count();
            $approvedRo = (clone $roQuery)->where('status', 'approved')->count();
            $rejectedRo = (clone $roQuery)->where('status', 'rejected')->count();
            $cancelledRo = (clone $roQuery)->where('status', 'cancelled')->count();
            $deletedRo = RoForm::onlyTrashed()
                ->when($departmentId !== null, fn($q) => $q->where('departments_id', $departmentId))
                ->when($fromDate, fn($q) => $q->whereDate('created_at', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('created_at', '<=', $toDate))
                ->count();

            $roVendorsCount = 0;
            if ($canViewVendor) {
                $roVendorsCount = $departmentId !== null
                    ? (clone $roQuery)->distinct()->count('vendor_id')
                    : Vendor::count();
            }

            $roFormsData = [
                'total' => $totalRo,
                'pending' => $pendingRo,
                'approved' => $approvedRo,
                'rejected' => $rejectedRo,
                'cancelled' => $cancelledRo,
                'deleted' => $deletedRo,
                'total_vendors' => $canViewVendor ? $roVendorsCount : null,
            ];

            // RO Financials (Permission: view-ro-revenue)
            if ($canViewRoRevenue) {
                $roFinancialsRaw = (clone $roQuery)->selectRaw("
                    COALESCE(SUM(selling_price), 0) as total_selling_price,
                    COALESCE(SUM(buying_price), 0) as total_buying_price,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM((selling_price * COALESCE(commission_percent, 0)) / 100), 0) as total_commission
                ")->first();

                $totalSelling = (float)($roFinancialsRaw->total_selling_price ?? 0);
                $totalBuying = (float)($roFinancialsRaw->total_buying_price ?? 0);
                $totalAmount = (float)($roFinancialsRaw->total_amount ?? 0);
                $totalCommission = (float)($roFinancialsRaw->total_commission ?? 0);
                $grossProfit = $totalSelling - $totalBuying;
                $netProfitLoss = $totalSelling - $totalBuying - $totalCommission;
                $profitMargin = $totalSelling > 0 ? round(($netProfitLoss / $totalSelling) * 100, 2) : 0;

                $financialStatus = 'break-even';
                if ($netProfitLoss > 0) {
                    $financialStatus = 'profit';
                } elseif ($netProfitLoss < 0) {
                    $financialStatus = 'loss';
                }

                $roFormsData['financials'] = [
                    'total_selling_price' => round($totalSelling, 2),
                    'total_buying_price' => round($totalBuying, 2),
                    'total_amount' => round($totalAmount, 2),
                    'total_commission' => round($totalCommission, 2),
                    'gross_profit' => round($grossProfit, 2),
                    'net_profit_loss' => round($netProfitLoss, 2),
                    'profit_margin_percent' => $profitMargin,
                    'financial_status' => $financialStatus,
                ];
            }

            $data['ro_forms'] = $roFormsData;
        }

        // ---------------------------------------------------------
        // 3. Domain Management Statistics (Permission: view-domain)
        // ---------------------------------------------------------
        if ($canViewDomain) {
            $domainQuery = Domain::query();

            if ($departmentId !== null) {
                $domainQuery->whereHas('salesPerson', function ($sq) use ($departmentId) {
                    $sq->where('department_id', $departmentId);
                });
            }

            if ($request->filled('branch_id')) {
                $domainQuery->where('branch_id', $request->branch_id);
            }

            if ($fromDate) {
                $domainQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $domainQuery->whereDate('created_at', '<=', $toDate);
            }

            $totalDomains = (clone $domainQuery)->count();
            $activeDomains = (clone $domainQuery)->where('system_status', 'Active')->count();
            $expiringSoonDomains = (clone $domainQuery)->where('system_status', 'Expiring soon')->count();
            $pendingRenewalDomains = (clone $domainQuery)->where('system_status', 'Pending Renewal')->count();
            $renewalInProgressDomains = (clone $domainQuery)->where('system_status', 'Renewal in Progress')->count();
            $expiredDomains = (clone $domainQuery)->where('system_status', 'Expired')->count();

            $totalDomainAmount = (float)(clone $domainQuery)->sum('total_amount');
            $domainIds = (clone $domainQuery)->pluck('id');
            $totalPaymentsCollected = (float)Payment::whereIn('domain_id', $domainIds)->sum('amount');
            $pendingPaymentAmount = max(0, $totalDomainAmount - $totalPaymentsCollected);

            $data['domains'] = [
                'total' => $totalDomains,
                'active' => $activeDomains,
                'expiring_soon' => $expiringSoonDomains,
                'pending_renewal' => $pendingRenewalDomains,
                'renewal_in_progress' => $renewalInProgressDomains,
                'expired' => $expiredDomains,
                'financials' => [
                    'total_domain_amount' => round($totalDomainAmount, 2),
                    'total_payments_collected' => round($totalPaymentsCollected, 2),
                    'pending_payment_amount' => round($pendingPaymentAmount, 2),
                ],
            ];
        }

        // ---------------------------------------------------------
        // 4. Purchase Invoices (Permission: view-purchase-invoice)
        // ---------------------------------------------------------
        if ($canViewPurchaseInvoice) {
            $invoiceQuery = PurchaseInvoice::query();

            if ($departmentId !== null && !$isAccounts) {
                $invoiceQuery->whereHas('user', function ($uq) use ($departmentId) {
                    $uq->where('department_id', $departmentId);
                });
            }

            if ($fromDate) {
                $invoiceQuery->whereDate('payment_date', '>=', $fromDate);
            }
            if ($toDate) {
                $invoiceQuery->whereDate('payment_date', '<=', $toDate);
            }

            $totalInvoices = (clone $invoiceQuery)->count();
            $totalInvoiceAmount = (float)(clone $invoiceQuery)->sum('amount');
            $pendingInvoices = (clone $invoiceQuery)->whereRaw('LOWER(status) = ?', ['pending'])->count();
            $approvedInvoices = (clone $invoiceQuery)->whereRaw('LOWER(status) = ?', ['approved'])->count();
            $rejectedInvoices = (clone $invoiceQuery)->whereRaw('LOWER(status) = ?', ['rejected'])->count();

            $data['purchase_invoices'] = [
                'total' => $totalInvoices,
                'total_amount' => round($totalInvoiceAmount, 2),
                'pending' => $pendingInvoices,
                'approved' => $approvedInvoices,
                'rejected' => $rejectedInvoices,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard fetched successfully',
            'data' => $data,
        ], 200);
    }
}
