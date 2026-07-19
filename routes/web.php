<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ForApprovalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TravelAllowance\TravelAllowanceController;

// Route::inertia('/', 'auth/Login', [
//     'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    if (Auth::user()->userType?->user_type_name === 'Contractor') {
        return redirect()->route('attendance.index');
    }

    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->get('auth/status', function () {
    return response()->json(['active' => true]);
})->name('auth.status');

Route::middleware(['auth', 'verified'])
    ->post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])
    ->name('notifications.mark-all-as-read');

Route::middleware(['auth', 'verified'])
    ->post('notifications/{notification}/open', [NotificationController::class, 'open'])
    ->name('notifications.open');



Route::middleware([
    'auth',
    'verified',
    'user-type:Superadmin,SOS Admin,SOS Standard,Client Admin,Client Standard,Contractor',
])->group(function () {


    // ATTENDANCE
    Route::get('attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.check-in');
    Route::post('attendance/lunch/start', [AttendanceController::class, 'startLunch'])
        ->name('attendance.lunch.start');
    Route::post('attendance/lunch/end', [AttendanceController::class, 'endLunch'])
        ->name('attendance.lunch.end');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.check-out');
    Route::post('attendance/forgot-check-out', [AttendanceController::class, 'storeForgotCheckOut'])
        ->name('attendance.forgot-check-out');
    Route::put('attendance/{attendance}', [AttendanceController::class, 'updateAttendance'])
        ->name('attendance.update');
    Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroyAttendance'])
        ->name('attendance.destroy');


    // ATTENDANCE & TRAVEL ALLOWANCE SUBMIT FOR APPROVAL
    Route::post('attendance/submit-for-approval', [AttendanceController::class, 'submitForApproval'])
        ->name('attendance.submit-for-approval');
    Route::get('travel-allowance', [TravelAllowanceController::class, 'index'])
        ->name('travel-allowance.index');
    Route::post('travel-allowance', [TravelAllowanceController::class, 'store'])
        ->name('travel-allowance.store');
    Route::put('travel-allowance/{travelAllowance}', [TravelAllowanceController::class, 'update'])
        ->name('travel-allowance.update');
    Route::delete('travel-allowance/{travelAllowance}', [TravelAllowanceController::class, 'destroy'])
        ->name('travel-allowance.destroy');
    Route::post('travel-allowance/submit-for-approval', [TravelAllowanceController::class, 'submitForApproval'])
        ->name('travel-allowance.submit-for-approval');
});



// Dashboard Route Permission
Route::middleware(['auth', 'verified', 'user-type:Superadmin,SOS Admin,SOS Standard,Client Admin,Client Standard'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});


// Client & Contractor Route Permission
Route::middleware(['auth', 'verified', 'user-type:Superadmin,SOS Admin,SOS Standard'])->group(function () {

    // Workspace Client
    Route::inertia('contractors', 'admin/contractors/index')->name('contractors.index');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/save-requests/{clientSaveRequest}', [ClientController::class, 'saveStatus'])->name('clients.save-status');

    // Workspace Contractor
    Route::inertia('contractors', 'admin/contractors/index')->name('contractors.index');
});

// Attendance and Travel Allowance Approval Route Permission

Route::middleware(['auth', 'verified', 'user-type:Superadmin,SOS Admin,Client Admin'])->group(function () {

    Route::get('for-approvals', [ForApprovalController::class, 'index'])
        ->name('for-approvals.index');

    Route::patch('attendance/{attendance}/approve', [AttendanceController::class, 'approve'])
        ->name('attendance.approve');

    Route::patch('attendance/{attendance}/reject', [AttendanceController::class, 'reject'])
        ->name('attendance.reject');

    Route::patch('travel-allowance/{travelAllowance}/approve', [TravelAllowanceController::class, 'approve'])
        ->name('travel-allowance.approve');

    Route::patch('travel-allowance/{travelAllowance}/reject', [TravelAllowanceController::class, 'reject'])
        ->name('travel-allowance.reject');
});

require __DIR__.'/settings.php';
