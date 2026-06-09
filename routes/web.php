<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;


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
    // Workspace
    Route::inertia('clients', 'admin/clients/index')->name('clients.index');
    Route::inertia('contractors', 'admin/contractors/index')->name('contractors.index');
    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('attendance/lunch/start', [AttendanceController::class, 'startLunch'])->name('attendance.lunch.start');
    Route::post('attendance/lunch/end', [AttendanceController::class, 'endLunch'])->name('attendance.lunch.end');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
});



require __DIR__.'/settings.php';
