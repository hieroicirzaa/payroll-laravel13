<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EmployeeDocumentController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PayrollPeriodController;
use App\Http\Controllers\Api\V1\SalaryComponentController;
use App\Http\Controllers\Api\V1\SelfServiceController;
use App\Http\Controllers\Api\V1\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/me/profile', [SelfServiceController::class, 'profile']);
        Route::get('/me/payrolls', [SelfServiceController::class, 'payrolls']);
        Route::get('/me/documents', [SelfServiceController::class, 'documents']);

        Route::get('/analytics/payroll-summary', [AnalyticsController::class, 'payrollSummary']);
        Route::get('/analytics/company-dashboard', [AnalyticsController::class, 'companyDashboard']);

        Route::middleware('role:super_admin')->group(function () {
            Route::apiResource('/companies', CompanyController::class);
            Route::post('/companies/{company}/admins', [CompanyController::class, 'storeAdmin']);
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
        });

        Route::middleware('role:super_admin,admin_company')->group(function () {
            Route::apiResource('/employees', EmployeeController::class);
            Route::post('/employees/{employee}/salary-components', [SalaryComponentController::class, 'assign']);
            Route::get('/salary-components', [SalaryComponentController::class, 'index']);
            Route::apiResource('/payroll-periods', PayrollPeriodController::class)->only(['index', 'store']);
            Route::post('/payroll-periods/{period}/generate', [PayrollPeriodController::class, 'generate']);
            Route::patch('/payrolls/{payroll}/mark-paid', [PayrollController::class, 'markPaid']);
            Route::patch('/payrolls/{payroll}/mark-failed', [PayrollController::class, 'markFailed']);
        });

        Route::get('/payrolls', [PayrollController::class, 'index']);
        Route::get('/payrolls/{payroll}', [PayrollController::class, 'show']);

        Route::get('/employees/{employee}/documents', [EmployeeDocumentController::class, 'index']);
        Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store']);
        Route::get('/documents/{document}/download', [EmployeeDocumentController::class, 'download']);
        Route::delete('/documents/{document}', [EmployeeDocumentController::class, 'destroy']);
    });
});
