<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Enterprise HRIS
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Core Dashboard & Profile
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // Employee Self-Service Attendance
    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])->name('attendance.my');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // Employee Self-Service Leaves
    Route::get('/my-leaves', [LeaveController::class, 'myLeaves'])->name('leaves.my');
    Route::get('/leaves/apply', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::get('/leaves/{leaveRequest}/attachment', [LeaveController::class, 'downloadAttachment'])->name('leaves.attachment');

    // Payslip View (Accessible by self or Admin/HR)
    Route::get('/payslips/{payslip}', [PayrollController::class, 'payslip'])->name('payroll.payslip');

    // HR & Administration (admin, hr_manager)
    Route::middleware('role:admin,hr_manager')->group(function () {
        // Employee Administration (Creation, Editing, Deletion, Export)
        Route::get('/employees/export', [EmployeeController::class, 'exportCsv'])->name('employees.export');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        // Departments & Job Designations
        Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
        Route::resource('designations', DesignationController::class)->except(['create', 'show', 'edit']);

        // Leave Configuration
        Route::resource('leave-types', LeaveTypeController::class)->except(['create', 'show', 'edit']);

        // Payroll Generation & Administration
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/batch/{batch}', [PayrollController::class, 'showBatch'])->name('payroll.batch');
        Route::post('/payroll/batch/{batch}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid');
        Route::get('/payroll/structures', [PayrollController::class, 'salaryStructures'])->name('payroll.structures');
    });

    // Management & HR Routes (admin, hr_manager, manager)
    Route::middleware('role:admin,hr_manager,manager')->group(function () {
        // Employee Directory (Read-only for managers)
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

        // Daily Attendance Management
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::put('/attendance/{attendance}/adjust', [AttendanceController::class, 'adjust'])->name('attendance.adjust');

        // Leave Requests Management & Approval
        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

        // Performance Reviews
        Route::resource('performance', PerformanceReviewController::class)->except(['edit', 'update']);

        // Company Announcements
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    // Employee 360 Profile (Accessible by self, manager, or Admin/HR)
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    // Super Admin Exclusives (admin)
    Route::middleware('role:admin')->group(function () {
        // Bulk Import Engine
        Route::get('/bulk-import', [BulkImportController::class, 'index'])->name('bulk-import.index');
        Route::post('/bulk-import/upload', [BulkImportController::class, 'upload'])->name('bulk-import.upload');
        Route::get('/bulk-import/template/{type}', [BulkImportController::class, 'downloadTemplate'])->name('bulk-import.template');
        Route::get('/bulk-import/logs', [BulkImportController::class, 'logs'])->name('bulk-import.logs');
        Route::get('/bulk-import/logs/{log}/errors', [BulkImportController::class, 'downloadErrorReport'])->name('bulk-import.errors');

        // System Audit Trail
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
