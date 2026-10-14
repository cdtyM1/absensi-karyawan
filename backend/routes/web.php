<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\ActiveAccount;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
});
Route::middleware(['auth', ActiveAccount::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AttendanceController::class, 'index'])->name('dashboard');
    Route::post('/attendance/in', [AttendanceController::class, 'checkIn'])->name('attendance.in');
    Route::post('/attendance/out', [AttendanceController::class, 'checkOut'])->name('attendance.out');
    Route::get('/attendance/{attendance}/selfie/{phase}', [AttendanceController::class, 'selfie'])->where('phase', 'in|out')->name('attendance.selfie');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/users', [AdminController::class, 'createUser'])->name('admin.users');
    Route::get('/admin/export', [AdminController::class, 'export'])->name('admin.export');
});
