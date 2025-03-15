<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AbsenceRequestController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\OverTimeRequestsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\MacAddressController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SalarySheetController;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExampleMail;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OnlineStatusController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\EmployeeStatisticsController;
use App\Http\Controllers\SpecialCaseController;
use App\Http\Controllers\WorkShiftController;
use App\Http\Controllers\EmployeeCompetitionController;


Route::get('/send-mail', function () {
    $data = [
        'name' => 'Recipient Name',
        'message' => 'This is a test email.'
    ];

    Mail::to('ahmeddfathy087@gmail.com')->send(new ExampleMail($data));

    return 'Email Sent Successfully!';
});

Route::get('/', function () {
    return view('welcome');
})->name('/');

Route::get('/welcome', function () {
    return "hello";
})->name('welcome');

Route::get('/mac-addresses', [MacAddressController::class, 'getMacAddresses']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // مسارات إدارة الورديات
    Route::resource('work-shifts', WorkShiftController::class);
    Route::patch('/work-shifts/{workShift}/toggle-status', [WorkShiftController::class, 'toggleStatus'])->name('work-shifts.toggle-status');

    // مسارات تعيين الورديات للمستخدمين
    Route::get('/users/assign-work-shifts', [UserController::class, 'assignWorkShifts'])->name('users.assign-work-shifts');
    Route::post('/users/save-work-shifts', [UserController::class, 'saveWorkShifts'])->name('users.save-work-shifts');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/absence-requests/{absenceRequest}/status', [AbsenceRequestController::class, 'updateStatus'])
        ->name('absence-requests.updateStatus');
});

Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::post('/permission-requests/{permissionRequest}/update-status', [PermissionRequestController::class, 'updateStatus'])
        ->name('permission-requests.update-status');
    Route::patch('/permission-requests/{permissionRequest}/reset-status', [PermissionRequestController::class, 'resetStatus'])
        ->name('permission-requests.reset-status');
    Route::patch('/permission-requests/{permissionRequest}/modify', [PermissionRequestController::class, 'modifyResponse'])
        ->name('permission-requests.modify');
    Route::patch('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
        ->name('permission-requests.update-return-status');

    Route::patch('/overtime-requests/{overTimeRequest}/respond', [OverTimeRequestsController::class, 'updateStatus'])
        ->name('overtime-requests.respond');

    Route::resource('overtime-requests', OverTimeRequestsController::class)->except(['show']);

    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/import', [AttendanceRecordController::class, 'import'])->name('attendance.import');

    Route::resource('/attendances', AttendanceController::class);
    Route::resource('/leaves', LeaveController::class);

    Route::resource('admin/notifications', AdminNotificationController::class, [
        'as' => 'admin'
    ]);
});

Route::middleware(['auth', 'role:manager,employee'])->group(function () {
    Route::resource('overtime-requests', OverTimeRequestsController::class);
    Route::resource('overtime-requests', OverTimeRequestsController::class);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');
    Route::get('/user/{employee_id}/attendance-preview', [DashboardController::class, 'previewAttendance'])
        ->name('user.previewAttendance')
        ->where('employee_id', '[0-9]+');
    Route::get('/user/{employee_id}/attendance-report', [DashboardController::class, 'generateAttendancePDF'])
        ->name('user.downloadAttendanceReport');

    Route::get('/salary-sheet/{userId}/{month}/{filename}', function ($employee_id, $month, $filename) {
        $user = Auth::user();
        if ($user->employee_id != $employee_id && $user->role != 'manager') {
            abort(403, 'Unauthorized access');
        }
        $filePath = storage_path("app/private/salary_sheets/{$employee_id}/{$month}/{$filename}");
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        return response()->file($filePath);
    })->middleware('auth');

    Route::resource('/permission-requests', PermissionRequestController::class);
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages/{receiver}', [ChatController::class, 'getMessages']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/mark-seen', [ChatController::class, 'markAsSeen']);
    Route::post('/status/update', [OnlineStatusController::class, 'updateStatus']);
    Route::get('/status/user/{userId}', [OnlineStatusController::class, 'getUserStatus']);

    Route::get('/attendance/preview/{employee_id}', [AttendanceController::class, 'preview'])
        ->name('attendance.preview');
});

Route::get('/salary-sheets', [SalarySheetController::class, 'index'])->name('salary-sheets.index');
Route::post('/salary-sheets/upload', [SalarySheetController::class, 'upload'])->name('salary-sheets.upload');

Route::get('/attendance/{employee_id}/pdf', [DashboardController::class, 'generateAttendancePDF'])
    ->name('attendance.pdf')
    ->middleware(['auth', 'role:manager']);

Route::resource('users', UserController::class);
Route::post('user/import', [UserController::class, 'import'])->name('user.import');;

Route::post('/users/{user}/roles-permissions', [UserController::class, 'updateRolesAndPermissions'])
    ->name('users.roles-permissions');
Route::get('/roles/{role}/permissions', [UserController::class, 'getRolePermissions'])
    ->name('roles.permissions')
    ->middleware('auth');
Route::post('/users/{user}/remove-roles', [UserController::class, 'removeRolesAndPermissions'])
    ->name('users.remove-roles');
Route::post('/users/{user}/reset-to-employee', [UserController::class, 'resetToEmployee'])
    ->name('users.reset-to-employee');
Route::get('/users-without-role', [UserController::class, 'getEmployeesWithoutRole'])
    ->name('users.without-role');
Route::post('/assign-employee-role', [UserController::class, 'assignEmployeeRole'])
    ->name('users.assign-employee-role');

Route::middleware(['auth'])->group(function () {
    Route::post('/overtime-requests/{overTimeRequest}/manager-status', [OverTimeRequestsController::class, 'updateManagerStatus'])
        ->name('overtime-requests.manager-status');
    Route::post('/overtime-requests/{overTimeRequest}/modify-manager-status', [OverTimeRequestsController::class, 'modifyManagerStatus'])
        ->name('overtime-requests.modify-manager-status');
    Route::post('/overtime-requests/{overTimeRequest}/reset-manager-status', [OverTimeRequestsController::class, 'resetManagerStatus'])
        ->name('overtime-requests.reset-manager-status');

    Route::post('/overtime-requests/{overTimeRequest}/hr-status', [OverTimeRequestsController::class, 'updateHrStatus'])
        ->name('overtime-requests.hr-status');
    Route::post('/overtime-requests/{overTimeRequest}/modify-hr-status', [OverTimeRequestsController::class, 'modifyHrStatus'])
        ->name('overtime-requests.modify-hr-status');
    Route::post('/overtime-requests/{overTimeRequest}/reset-hr-status', [OverTimeRequestsController::class, 'resetHrStatus'])
        ->name('overtime-requests.reset-hr-status');

    Route::get('/employee-statistics', [EmployeeStatisticsController::class, 'index'])
        ->name('employee-statistics.index');
    Route::get('/employee-statistics/{id}', [EmployeeStatisticsController::class, 'getEmployeeDetails'])
        ->name('employee-statistics.details');

    // Employee Competition Routes
    Route::get('/employee-competition', [EmployeeCompetitionController::class, 'index'])
        ->name('employee-competition.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/permission-requests', [PermissionRequestController::class, 'index'])
        ->name('permission-requests.index');

    Route::post('/permission-requests', [PermissionRequestController::class, 'store'])
        ->name('permission-requests.store');

    Route::put('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'update'])
        ->name('permission-requests.update');

    Route::delete('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'destroy'])
        ->name('permission-requests.destroy');

    Route::post('/permission-requests/{permissionRequest}/manager-status', [PermissionRequestController::class, 'updateManagerStatus'])
        ->name('permission-requests.manager-status');

    Route::post('/permission-requests/{permissionRequest}/modify-manager-status', [PermissionRequestController::class, 'modifyManagerStatus'])
        ->name('permission-requests.modify-manager-status');

    Route::post('/permission-requests/{permissionRequest}/reset-manager-status', [PermissionRequestController::class, 'resetManagerStatus'])
        ->name('permission-requests.reset-manager-status');

    Route::post('/permission-requests/{permissionRequest}/hr-status', [PermissionRequestController::class, 'updateHrStatus'])
        ->name('permission-requests.hr-status');

    Route::post('/permission-requests/{permissionRequest}/modify-hr-status', [PermissionRequestController::class, 'modifyHrStatus'])
        ->name('permission-requests.modify-hr-status');

    Route::post('/permission-requests/{permissionRequest}/reset-hr-status', [PermissionRequestController::class, 'resetHrStatus'])
        ->name('permission-requests.reset-hr-status');

    Route::patch('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
        ->name('permission-requests.return-status');

    Route::post('/permission-requests/check-end-of-day', [PermissionRequestController::class, 'checkEndOfDay'])
        ->name('permission-requests.check-end-of-day');

    Route::resource('/absence-requests', AbsenceRequestController::class)
        ->middleware(['permission:view_absence|create_absence|update_absence|delete_absence']);

    Route::patch('/absence-requests/{absenceRequest}/reset-status', [AbsenceRequestController::class, 'resetStatus'])
        ->name('absence-requests.reset-status')
        ->middleware(['permission:hr_respond_absence_request|manager_respond_absence_request']);

    Route::patch('/absence-requests/{id}/modify', [AbsenceRequestController::class, 'modifyResponse'])
        ->name('absence-requests.modify')
        ->middleware(['permission:hr_respond_absence_request|manager_respond_absence_request']);

    Route::post('/absence-requests/{absenceRequest}/status', [AbsenceRequestController::class, 'updateStatus'])
        ->name('absence-requests.updateStatus')
        ->middleware(['permission:hr_respond_absence_request|manager_respond_absence_request']);
});

Route::get('/users/{user}/forbidden-permissions', [UserController::class, 'getForbiddenPermissions'])
    ->name('users.forbidden-permissions');

Route::get('/roles/{role}/permissions', [UserController::class, 'getRolePermissions'])
    ->name('roles.permissions');

Route::post('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
    ->name('permission-requests.update-return-status');

Route::get('/employee-statistics/absences/{employee_id}', [EmployeeStatisticsController::class, 'getAbsences']);
Route::get('/employee-statistics/permissions/{employee_id}', [EmployeeStatisticsController::class, 'getPermissions']);
Route::get('/employee-statistics/overtimes/{employee_id}', [EmployeeStatisticsController::class, 'getOvertimes']);
Route::get('/employee-statistics/leaves/{employee_id}', [EmployeeStatisticsController::class, 'getLeaves']);
Route::get('/employee-statistics/current-month-leaves/{employee_id}', [EmployeeStatisticsController::class, 'getCurrentMonthLeaves'])
    ->name('employee-statistics.current-month-leaves');

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])
        ->name('notifications.unread');
    Route::post('/notifications/{decision}/acknowledge', [NotificationController::class, 'acknowledge'])
        ->name('notifications.acknowledge');
});

Route::resource('special-cases', SpecialCaseController::class);
Route::post('special-cases/import', [SpecialCaseController::class, 'import'])->name('special-cases.import');

Route::post('/fcm-token', [App\Http\Controllers\FcmTokenController::class, 'update'])
    ->middleware('auth')
    ->name('fcm.token.update');


