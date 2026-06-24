<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Settings\UserSettingsController;
use App\Http\Controllers\Admin\Settings\CompanySettingsController;




Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
   Route::get('settings/company', [CompanySettingsController::class, 'index'])
    ->name('settings.company.index');
    Route::get('settings/users', [UserSettingsController::class, 'index'])
    ->name('settings.users.index');
    Route::post('settings/users', [UserSettingsController::class, 'store'])
    ->name('settings.users.store');
    Route::patch('settings/users/{user}/toggle-status',[UserSettingsController::class, 'toggleStatus'])->name('settings.users.toggle-status');
});
