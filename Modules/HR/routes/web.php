<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController as ItsmAuthController;
use Modules\HR\Http\Controllers\DashboardController;
use Modules\HR\Http\Controllers\EmployeeController;
use Modules\HR\Http\Controllers\DepartmentController;
use Modules\HR\Http\Controllers\AttendanceController;
use Modules\HR\Http\Controllers\EmployeeOnboardingController;
use Modules\HR\Http\Controllers\ReportsAnalyticsController;
use Modules\HR\Http\Controllers\DeliveryDriverController;
use Modules\HR\Http\Controllers\LeaveRequestController;
use Modules\HR\Http\Controllers\EmployeeProfileController;
use Modules\HR\Models\Attendance;

Route::get('/', function () {
    return redirect()->route('hr.dashboard');
});

Route::middleware('hr.access')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Preserve existing bookmarks but make ITSM the only employee landing page.
    Route::get('/employee-dashboard', function () {
        return redirect()->route('employee.portal');
    })->name('employee.dashboard');

    Route::get('/employee-attendance', [ReportsAnalyticsController::class, 'selfAttendance'])
        ->name('employee.attendance');

    Route::get('/employee-profile', [EmployeeProfileController::class, 'show'])
        ->name('employee.profile');

    Route::get('/profile-pictures/{filename}', [EmployeeProfileController::class, 'picture'])
        ->where('filename', '[^/]+')
        ->name('profile-picture');

    Route::get('/employee-leave', [LeaveRequestController::class, 'employeeLeave'])
        ->name('employee.leave');
    Route::post('/employee-leave', [LeaveRequestController::class, 'store'])
        ->name('employee.leave.submit');

    Route::post('/logout', [ItsmAuthController::class, 'logout'])->name('logout');

    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('hr.permission:hr.view_employee_records')->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->middleware('hr.permission:hr.create_employees')->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('hr.permission:hr.create_employees')->name('employees.store');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])->middleware('hr.permission:hr.view_employee_records')->name('employees.show');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('hr.permission:hr.edit_employee_records')->name('employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->middleware('hr.permission:hr.delete_employees')->name('employees.destroy');

    Route::get('/drivers', [DeliveryDriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [DeliveryDriverController::class, 'store'])->name('drivers.store');
    Route::put('/drivers/{driver}', [DeliveryDriverController::class, 'update'])->name('drivers.update');

    Route::get('/departments', [DepartmentController::class, 'index'])->middleware('hr.permission:hr.manage_departments')
        ->name('departments.index');
    Route::get('/departments/{slug}', [DepartmentController::class, 'show'])->middleware('hr.permission:hr.manage_departments')
        ->name('departments.show');

    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/step1', [EmployeeOnboardingController::class, 'step1'])->middleware('hr.permission:hr.manage_onboarding')->name('step1');
        Route::post('/step1', [EmployeeOnboardingController::class, 'storeStep1'])->middleware('hr.permission:hr.manage_onboarding')->name('storeStep1');

        Route::get('/step2', [EmployeeOnboardingController::class, 'step2'])->middleware('hr.permission:hr.manage_onboarding')->name('step2');
        Route::post('/step2', [EmployeeOnboardingController::class, 'storeStep2'])->middleware('hr.permission:hr.manage_onboarding')->name('storeStep2');

        Route::get('/step3', [EmployeeOnboardingController::class, 'step3'])->middleware('hr.permission:hr.manage_onboarding')->name('step3');
        Route::post('/step3', [EmployeeOnboardingController::class, 'storeStep3'])->middleware('hr.permission:hr.manage_onboarding')->name('storeStep3');

        Route::get('/step4', [EmployeeOnboardingController::class, 'step4'])->middleware('hr.permission:hr.manage_onboarding')->name('step4');
        Route::post('/step4', [EmployeeOnboardingController::class, 'storeStep4'])->middleware('hr.permission:hr.manage_onboarding')->name('storeStep4');

        Route::get('/success', [EmployeeOnboardingController::class, 'success'])->name('success');
    });

    Route::get('/reports-analytics/attendance-overview', [ReportsAnalyticsController::class, 'index'])->middleware('hr.permission:hr.view_attendance_reports')
        ->name('reports-analytics.attendance-overview');

    Route::get('/reports-analytics/employee-attendance/{employee}', [ReportsAnalyticsController::class, 'employeeAttendance'])->middleware('hr.permission:hr.view_attendance_reports')
        ->name('reports-analytics.employee-attendance');

    Route::get('/reports-analytics/leave', [ReportsAnalyticsController::class, 'leave'])->middleware('hr.permission:hr.manage_leave_requests')
        ->name('reports-analytics.leave');

    Route::get('/leave-management', [LeaveRequestController::class, 'index'])->middleware('hr.permission:hr.manage_leave_requests')
        ->name('leave-management.index');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->middleware('hr.permission:hr.manage_leave_requests')
        ->name('leave-requests.show');
    Route::post('/leave-requests/{leaveRequest}/review', [LeaveRequestController::class, 'review'])->middleware('hr.permission:hr.approve_leave')
        ->name('leave-requests.review');

    Route::get('/attendance/today-count', function () {
        return response()->json([
            'count' => Attendance::whereDate('attendance_date', today())
                ->where('client_id', (int) session('employee_client_id'))
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->count()
        ]);
    });
});

Route::get('/clockinout', function () {
    return view('clockinout.index', [
        'attendanceTimezone' => session('attendance_timezone'),
    ]);
})->name('clockinout');

Route::post('/clock-in', [AttendanceController::class, 'clockIn'])
    ->name('clockinout.index');
