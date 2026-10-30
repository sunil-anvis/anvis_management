<?php

use App\Http\Controllers\Api\AccountsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\RoFormController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| Protected Routes (custom_auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['custom_auth'])->group(function () {
    // Auth & Profile
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Users & Roles assignment
    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole']);
    Route::delete('/users/{user}/roles', [UserController::class, 'removeRole']);

    // Roles, Permissions, Branches, Departments
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('departments', DepartmentController::class);

    // Vendor CRUD
    Route::get('/vendor', [VendorController::class, 'index']);
    Route::post('/vendor', [VendorController::class, 'store']);
    Route::get('/vendor/{id}', [VendorController::class, 'show']);
    Route::patch('/vendor/{id}', [VendorController::class, 'update']);
    Route::delete('/vendor/{id}', [VendorController::class, 'destroy']);

    // RO Form CRUD & Revenue Summary
    Route::get('/roForm/revenue-summary', [RoFormController::class, 'revenueSummary']);
    Route::get('/ro-form/revenue-summary', [RoFormController::class, 'revenueSummary']);
    Route::get('/roForm', [RoFormController::class, 'index']);
    Route::post('/roForm', [RoFormController::class, 'store']);
    Route::get('/roForm/{id}', [RoFormController::class, 'show']);
    Route::patch('/roForm/{id}', [RoFormController::class, 'update']);
    Route::delete('/roForm/{id}', [RoFormController::class, 'destroy']);

    // RO Form Approve & Reject apis
    Route::post('/roForm/{id}/send-email', [RoFormController::class, 'sendEmail']);
    Route::patch('/roForm/{id}/approve', [RoFormController::class, 'approve']);
    Route::patch('/roForm/{id}/reject', [RoFormController::class, 'reject']);
    Route::patch('/roForm/{id}/cancel', [RoFormController::class, 'cancel']);
    Route::post('/roForm/{id}/send-to-accounts', [RoFormController::class, 'sendToAccounts']);
    Route::get('/roForm/{id}/download-pdf', [RoFormController::class, 'downloadPdf']);

    // Accounts CRUD & Export
    Route::prefix('accounts')->group(function () {
        Route::get('/export', [AccountsController::class, 'export']);
        Route::get('/', [AccountsController::class, 'index']);
        Route::post('/', [AccountsController::class, 'store']);
        Route::get('/{id}', [AccountsController::class, 'show']);
        Route::patch('/{id}', [AccountsController::class, 'update']);
        Route::delete('/{id}', [AccountsController::class, 'destroy']);
    });

    // Domains
    Route::post('/domains/bulk-update', [DomainController::class, 'bulkUpdate']);
    Route::apiResource('domains', DomainController::class);

    // Payments
    Route::apiResource('payments', PaymentController::class);

    // Purchase Invoices
    Route::get('/purchase-invoices/{id}/file', [PurchaseInvoiceController::class, 'downloadFile']);
    Route::apiResource('purchase-invoices', PurchaseInvoiceController::class);
});
