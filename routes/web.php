<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Auth;


// Route::inertia('/', 'auth/Login', [
//     'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    // HOME
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    // Workspace
    Route::inertia('clients', 'admin/clients/index')->name('clients.index');
    Route::inertia('contractors', 'admin/contractors/index')->name('contractors.index');
    Route::inertia('attendance', 'attendance/index')->name('attendance.index');
});

require __DIR__.'/settings.php';
