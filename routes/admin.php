<?php

use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserDeactivationController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
    'can:access-admin',
])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/users')->name('index');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::post('users/{user}/admin', [UserAdminController::class, 'store'])->name('users.admin.store');
    Route::delete('users/{user}/admin', [UserAdminController::class, 'destroy'])->name('users.admin.destroy');

    Route::post('users/{user}/deactivation', [UserDeactivationController::class, 'store'])->name('users.deactivation.store');
    Route::delete('users/{user}/deactivation', [UserDeactivationController::class, 'destroy'])->name('users.deactivation.destroy');
});
