<?php

use App\Http\Controllers\WorkRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\ActiveAccount;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
});
Route::middleware(['auth', ActiveAccount::class])->group(function () {
    Route::get('/requests', [WorkRequestController::class, 'index'])->name('requests');
    Route::post('/requests', [WorkRequestController::class, 'store'])->name('requests.store');
    Route::get('/admin/requests', [WorkRequestController::class, 'admin'])->name('requests.admin');
    Route::patch('/admin/requests/{workRequest}', [WorkRequestController::class, 'review'])->name('requests.review');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AttendanceController::class, 'index'])->name('dashboard');
    Route::post('/attendance/in', [AttendanceController::class, 'checkIn'])->name('attendance.in');
    Route::post('/attendance/out', [AttendanceController::class, 'checkOut'])->name('attendance.out');
    Route::get('/attendance/{attendance}/selfie/{phase}', [AttendanceController::class, 'selfie'])->where('phase', 'in|out')->name('attendance.selfie');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])->name('admin.users.edit');
    Route::patch('/admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/admin/users', [AdminController::class, 'createUser'])->name('admin.users');
    Route::get('/admin/export', [AdminController::class, 'export'])->name('admin.export');
});
