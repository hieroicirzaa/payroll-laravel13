<?php

use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\Auth\PasswordResetController;
use App\Http\Controllers\Web\CompanyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\PayrollController;
use App\Http\Controllers\Web\SalaryComponentController;
use App\Http\Controllers\Web\SptReportController;
use App\Http\Controllers\Web\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', fn () => Inertia::render('Profile/Show'))->name('profile.show');

    Route::middleware('role:super_admin')->group(function (): void {
        Route::resource('companies', CompanyController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('/companies/{company}/restore', [CompanyController::class, 'restore'])->name('companies.restore');

        Route::resource('users', UserManagementController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('/users/{user}/restore', [UserManagementController::class, 'restore'])->name('users.restore');

        Route::get('/salary-components', [SalaryComponentController::class, 'index'])->name('salary-components.index');
        Route::post('/salary-components', [SalaryComponentController::class, 'store'])->name('salary-components.store');
        Route::put('/salary-components/{salaryComponent}', [SalaryComponentController::class, 'update'])->name('salary-components.update');
        Route::delete('/salary-components/{salaryComponent}', [SalaryComponentController::class, 'destroy'])->name('salary-components.destroy');
        Route::patch('/salary-components/{salaryComponent}/restore', [SalaryComponentController::class, 'restore'])->name('salary-components.restore');
    });

    Route::middleware('role:super_admin,admin_company')->group(function (): void {
        Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/employees-import/template', [EmployeeController::class, 'downloadImportTemplate'])->name('employees.import-template');
        Route::get('/employees-export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::post('/employees-import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::patch('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
        Route::post('/employees/{employee}/salary-components', [EmployeeController::class, 'assignComponent'])->name('employees.salary-components.store');
        Route::delete('/employee-salary-components/{component}', [EmployeeController::class, 'removeComponent'])->name('employee-salary-components.destroy');

        Route::post('/payroll-periods', [PayrollController::class, 'storePeriod'])->name('payroll-periods.store');
        Route::put('/payroll-periods/{period}', [PayrollController::class, 'updatePeriod'])->name('payroll-periods.update');
        Route::delete('/payroll-periods/{period}', [PayrollController::class, 'destroyPeriod'])->name('payroll-periods.destroy');
        Route::post('/payroll-periods/{period}/generate', [PayrollController::class, 'generate'])->name('payroll-periods.generate');
        Route::patch('/payrolls/{payroll}/paid', [PayrollController::class, 'markPaid'])->name('payrolls.paid');
        Route::patch('/payrolls/{payroll}/failed', [PayrollController::class, 'markFailed'])->name('payrolls.failed');
        Route::delete('/payrolls/{payroll}', [PayrollController::class, 'destroyPayroll'])->name('payrolls.destroy');

        Route::post('/spt-reports', [SptReportController::class, 'store'])->name('spt-reports.store');
        Route::patch('/spt-reports/{sptReport}', [SptReportController::class, 'update'])->name('spt-reports.update');
        Route::delete('/spt-reports/{sptReport}', [SptReportController::class, 'destroy'])->name('spt-reports.destroy');
    });

    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payrolls/{payroll}/slip', [PayrollController::class, 'slip'])->name('payrolls.slip');
    Route::get('/payrolls/{payroll}/slip/download', [PayrollController::class, 'downloadSlipPdf'])->name('payrolls.slip.download');
    Route::post('/payrolls/{payroll}/slip/email', [PayrollController::class, 'emailSlip'])->name('payrolls.slip.email');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('/documents/{document}/email', [DocumentController::class, 'email'])->name('documents.email');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/spt-reports', [SptReportController::class, 'index'])->name('spt-reports.index');
});
