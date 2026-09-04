<?php

use App\Http\Controllers\Admin\AtollController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\IslandController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserDepartmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\ImportantContactController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ScheduledReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskInteractionController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// --- Auth pages ---
Route::get('/auth', [AuthController::class, 'showLogin'])->name('auth.show');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login')->middleware('throttle:5,1');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');

Route::get('/', [PageController::class, 'landing'])->name('home');

// --- Authenticated pages ---
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:view_dashboard')->name('dashboard');
    Route::get('/tasks', [TaskController::class, 'index'])->middleware('permission:view_tasks')->name('tasks.index');
    Route::get('/hospitals', [HospitalController::class, 'index'])->middleware('permission:view_hospitals')->name('hospitals.index');
    Route::get('/coordinators', [PageController::class, 'coordinators'])->name('coordinators.index');
    Route::get('/important-contacts', [ImportantContactController::class, 'index'])->name('important-contacts.index');
    Route::get('/important-contacts-admin', [ImportantContactController::class, 'admin'])->name('important-contacts.admin');
    Route::get('/critical-staff-leave-management', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/hospital-operations', [OperationsController::class, 'index'])->middleware('permission:view_operations')->name('operations.index');
    Route::get('/hospital-operations/executive-report.pdf', [OperationsController::class, 'executivePdf'])->middleware('permission:view_reports')->name('operations.pdf');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:view_reports')->name('reports.index');
    Route::get('/role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/install', [PageController::class, 'install'])->name('install.index');

    // --- JSON data endpoints ---
    Route::prefix('api')->middleware('throttle:api')->group(function () {
        Route::get('/statistics', [DashboardController::class, 'statistics'])->middleware('permission:view_dashboard');
        Route::get('/search', [SearchController::class, 'search']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/notifications', [NotificationController::class, 'clearAll']);
        Route::get('/push-subscriptions/config', [PushSubscriptionController::class, 'config']);
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);

        // Uploads
        Route::post('/upload', [UploadController::class, 'upload']);

        // Tasks
        Route::get('/tasks', [TaskController::class, 'apiIndex'])->middleware('permission:view_tasks');
        Route::post('/tasks', [TaskController::class, 'store'])->middleware('permission:create_tasks');
        Route::patch('/tasks/{id}', [TaskController::class, 'update'])->middleware('permission:edit_tasks');
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->middleware('permission:delete_tasks');

        // Task interactions (comments / activities / call logs)
        Route::get('/tasks/{taskId}/comments', [TaskInteractionController::class, 'comments']);
        Route::post('/tasks/{taskId}/comments', [TaskInteractionController::class, 'storeComment'])->middleware('permission:edit_tasks');
        Route::delete('/tasks/{taskId}/comments/{commentId}', [TaskInteractionController::class, 'destroyComment'])->middleware('permission:edit_tasks');
        Route::get('/tasks/{taskId}/activities', [TaskInteractionController::class, 'activities']);
        Route::get('/tasks/{taskId}/call-logs', [TaskInteractionController::class, 'callLogs']);
        Route::post('/tasks/{taskId}/call-logs', [TaskInteractionController::class, 'storeCallLog'])->middleware('permission:edit_tasks');
        Route::patch('/tasks/{taskId}/call-logs/{logId}', [TaskInteractionController::class, 'updateCallLog'])->middleware('permission:edit_tasks');
        Route::delete('/tasks/{taskId}/call-logs/{logId}', [TaskInteractionController::class, 'destroyCallLog'])->middleware('permission:edit_tasks');

        // Admin user management
        Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:view_users');
        Route::post('/users', [AdminUserController::class, 'createUser'])->middleware('permission:manage_users');
        Route::patch('/users/{id}', [AdminUserController::class, 'update'])->middleware('permission:manage_users');
        Route::post('/users/{id}/role', [AdminUserController::class, 'updateRole'])->middleware('permission:manage_users');
        Route::delete('/users/{id}', [AdminUserController::class, 'deleteUser'])->middleware('permission:manage_users');

        // Atolls / Islands / Departments (admin + supervisor)
        Route::middleware('permission:manage_atolls')->group(function () {
            Route::get('/atolls', [AtollController::class, 'index']);
            Route::post('/atolls', [AtollController::class, 'store']);
            Route::post('/atolls/import', [AtollController::class, 'bulkImport']);
            Route::patch('/atolls/{id}', [AtollController::class, 'update']);
            Route::delete('/atolls/{id}', [AtollController::class, 'destroy']);

        });

        Route::middleware('permission:manage_islands')->group(function () {
            Route::get('/islands', [IslandController::class, 'index']); Route::post('/islands', [IslandController::class, 'store']); Route::post('/islands/bulk', [IslandController::class, 'bulkAdd']); Route::patch('/islands/{id}', [IslandController::class, 'update']); Route::delete('/islands/{id}', [IslandController::class, 'destroy']);
        });
        Route::middleware('permission:manage_departments')->group(function () {
            Route::get('/departments', [DepartmentController::class, 'index']); Route::post('/departments', [DepartmentController::class, 'store']); Route::patch('/departments/{id}', [DepartmentController::class, 'update']); Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
            Route::get('/user-departments', [UserDepartmentController::class, 'index']); Route::post('/user-departments', [UserDepartmentController::class, 'store']); Route::patch('/user-departments/{id}', [UserDepartmentController::class, 'update']); Route::delete('/user-departments/{id}', [UserDepartmentController::class, 'destroy']);
        });
        Route::get('/hospital-contacts', [HospitalController::class, 'contacts'])->middleware('permission:view_hospitals');
        Route::post('/hospital-contacts', [HospitalController::class, 'storeContact'])->middleware('permission:manage_hospitals');
        Route::patch('/hospital-contacts/{id}', [HospitalController::class, 'updateContact'])->middleware('permission:manage_hospitals');
        Route::post('/hospital-contacts/deactivate', [HospitalController::class, 'deactivateContact'])->middleware('permission:manage_hospitals');
        Route::post('/hospital-contacts/import', [HospitalController::class, 'importCsv'])->middleware('permission:manage_hospitals');

        // Hospital profile read/write is intentionally outside the
        // admin/supervisor group so staff can view and maintain their own
        // facility profile. Per-island access is enforced in
        // HospitalController::showProfile/saveProfile.
        Route::get('/hospital-profiles/{hospitalContactId}', [HospitalController::class, 'showProfile'])->middleware('permission:view_hospitals');
        Route::post('/hospital-profiles', [HospitalController::class, 'saveProfile'])->middleware('permission:edit_hospital_profiles');

        // Coordinators (view all; edit non-staff)
        Route::get('/coordinators', [CoordinatorController::class, 'data']);
        Route::post('/coordinators/assignments', [CoordinatorController::class, 'updateAssignments']);
        Route::post('/coordinators/deactivate', [CoordinatorController::class, 'deactivate']);

        // Important contacts
        Route::get('/important-contacts', [ImportantContactController::class, 'data']);
        Route::post('/important-contacts', [ImportantContactController::class, 'store'])->middleware('role:admin');
        Route::post('/important-contacts/deactivate', [ImportantContactController::class, 'bulkDeactivate'])->middleware('role:admin');
        Route::patch('/important-contacts/{id}', [ImportantContactController::class, 'update'])->middleware('role:admin');
        Route::post('/important-contacts/{id}/deactivate', [ImportantContactController::class, 'deactivate'])->middleware('role:admin');

        // Role permissions
        Route::get('/role-permissions', [RolePermissionController::class, 'data']);
        Route::post('/role-permissions', [RolePermissionController::class, 'store'])->middleware('role:admin');
        Route::patch('/role-permissions/{id}', [RolePermissionController::class, 'update'])->middleware('role:admin');
        Route::delete('/role-permissions/{id}', [RolePermissionController::class, 'destroy'])->middleware('role:admin');

        // Leaves
        Route::get('/leave/assignees/me', [LeaveController::class, 'assigneesMe']);
        Route::get('/leaves', [LeaveController::class, 'data']);
        Route::post('/leaves', [LeaveController::class, 'store']);
        Route::patch('/leaves/{id}', [LeaveController::class, 'update']);
        Route::delete('/leaves/{id}', [LeaveController::class, 'destroy']);

        Route::get('/availability-setup', [LeaveController::class, 'setupData']);
        Route::post('/availability-setup', [LeaveController::class, 'storeSetup']);
        Route::patch('/availability-setup/{id}', [LeaveController::class, 'updateSetup']);
        Route::delete('/availability-setup/{id}', [LeaveController::class, 'destroySetup']);

        Route::get('/operations', [OperationsController::class, 'data'])->middleware('permission:view_operations');
        Route::middleware('permission:manage_operations')->group(function () {
            Route::post('/operations/incidents', [OperationsController::class, 'storeIncident']);
            Route::patch('/operations/incidents/{id}', [OperationsController::class, 'updateIncident']);
            Route::post('/operations/faults', [OperationsController::class, 'storeFault']);
            Route::patch('/operations/faults/{id}', [OperationsController::class, 'updateFault']);
            Route::post('/operations/transport', [OperationsController::class, 'storeTransport']);
            Route::patch('/operations/transport/{id}', [OperationsController::class, 'updateTransport']);
            Route::post('/operations/expiry-items', [OperationsController::class, 'storeExpiry']);
            Route::patch('/operations/expiry-items/{id}', [OperationsController::class, 'updateExpiry']);
            Route::post('/operations/documents', [OperationsController::class, 'storeDocument']);
            Route::delete('/operations/documents/{id}', [OperationsController::class, 'destroyDocument']);
            Route::post('/operations/action-task', [OperationsController::class, 'createActionTask']);
        });

        // Scheduled reports
        Route::get('/scheduled-reports', [ScheduledReportController::class, 'index'])->middleware('permission:view_reports');
        Route::post('/scheduled-reports', [ScheduledReportController::class, 'store'])->middleware('permission:view_reports');
        Route::patch('/scheduled-reports/{id}', [ScheduledReportController::class, 'update'])->middleware('permission:view_reports');
        Route::delete('/scheduled-reports/{id}', [ScheduledReportController::class, 'destroy'])->middleware('permission:view_reports');

        // Settings
        Route::patch('/profile', [SettingsController::class, 'updateProfile']);
        Route::post('/profile/avatar', [SettingsController::class, 'updateAvatar']);
        Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');

        // Reports export
        Route::get('/reports/export/tasks', [ReportController::class, 'exportTasksCsv'])->middleware('permission:view_reports');
        Route::get('/reports/export/hospital-contacts', [ReportController::class, 'exportHospitalContactsCsv'])->middleware('permission:view_reports');
        Route::get('/reports/export/hospital-profiles', [ReportController::class, 'exportHospitalProfilesCsv'])->middleware('permission:view_reports');
        Route::get('/reports/generate/{type}', [ReportController::class, 'generate'])->middleware('permission:view_reports');
    });
});
