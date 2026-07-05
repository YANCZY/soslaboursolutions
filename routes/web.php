<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ForApprovalController;
use App\Http\Controllers\NotificationController;

// Route::inertia('/', 'auth/Login', [
//     'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('auth/status', function () {
        return response()->json(['active' => true]);
    })->name('auth.status');
    // HOME
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    // Workspace Contractor
    Route::inertia('contractors', 'admin/contractors/index')->name('contractors.index');
    // Workspace Clients
    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/save-requests/{clientSaveRequest}', [ClientController::class, 'saveStatus'])->name('clients.save-status');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('attendance/lunch/start', [AttendanceController::class, 'startLunch'])->name('attendance.lunch.start');
    Route::post('attendance/lunch/end', [AttendanceController::class, 'endLunch'])->name('attendance.lunch.end');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::post('attendance/forgot-check-out', [AttendanceController::class, 'storeForgotCheckOut'])->name('attendance.forgot-check-out');
    Route::post('attendance/submit-for-approval', [AttendanceController::class, 'submitForApproval'])
        ->name('attendance.submit-for-approval');
    Route::put('attendance/{attendance}', [AttendanceController::class, 'updateAttendance'])->name('attendance.update');
    Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroyAttendance'])->name('attendance.destroy');
    // For Approvals
    Route::get('for-approvals', [ForApprovalController::class, 'index'])
        ->name('for-approvals.index');
    // Notifications
    Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
});



require __DIR__.'/settings.php';
