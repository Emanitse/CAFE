<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\AuditTrailController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Two-Factor Authentication Login Challenge
Route::get('/login/2fa', [AuthController::class, 'showTwoFactorChallenge'])->name('login.2fa');
Route::post('/login/2fa', [AuthController::class, 'verifyTwoFactorChallenge'])->name('login.2fa.verify');
Route::post('/login/2fa/cancel', [AuthController::class, 'cancelTwoFactorChallenge'])->name('login.2fa.cancel');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

// Protected routes 
Route::middleware(['role'])->group(function () {
    
    // Home - accessible to all 
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/data', [HomeController::class, 'getData'])->name('home.data');

    // Orders - accessible to all 
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/checkout', [OrderController::class, 'store'])->name('checkout');
    });

    // Menu Management - Admin and Manager only
    Route::middleware('role:admin, manager, superadmin')->prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::put('/{id}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{id}', [MenuController::class, 'destroy'])->name('destroy');
    });

    // Categories Management - Admin and Manager only
    Route::middleware('role:admin, manager, superadmin')->prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // Reports - Admin and Superadmin only
    Route::middleware('role:admin, superadmin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/history', [ReportController::class, 'getOrderHistory'])->name('reports.history');
        Route::get('/reports/categories', [ReportController::class, 'getCategories'])->name('reports.categories');
        Route::get('/reports/items', [ReportController::class, 'getItems'])->name('reports.items');
        Route::post('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
    });

    // System Backup & Restore - Admin & Superadmin only
    Route::middleware('role:admin, superadmin')->prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/export', [BackupController::class, 'export'])->name('export');
        Route::post('/snapshot', [BackupController::class, 'createSnapshot'])->name('snapshot');
        Route::get('/snapshot/{filename}/download', [BackupController::class, 'downloadSnapshot'])->name('downloadSnapshot');
        Route::delete('/snapshot/{filename}', [BackupController::class, 'deleteSnapshot'])->name('deleteSnapshot');
        Route::post('/restore', [BackupController::class, 'restore'])->name('restore');
    });
    
    // Users Management - Admin & Superadmin
    Route::middleware('role:admin, superadmin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/list', [UserController::class, 'getUsers'])->name('list');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle');
        Route::patch('/{id}/reset-2fa', [UserController::class, 'resetTwoFactor'])->name('reset2fa');
    });

    Route::middleware('role:superadmin')->prefix('audit-trail')->name('audit.')->group(function () {
        Route::get('/', [AuditTrailController::class, 'index'])->name('index');
        Route::get('/access-logs', [AuditTrailController::class, 'getAccessLogs'])->name('accessLogs');
        Route::get('/error-logs', [AuditTrailController::class, 'getErrorLogs'])->name('errorLogs');
        Route::post('/export-access-pdf', [AuditTrailController::class, 'exportAccessPdf'])->name('exportAccessPdf');
        Route::post('/export-error-pdf', [AuditTrailController::class, 'exportErrorPdf'])->name('exportErrorPdf');
    });

    // Profile Settings - accessible to all authenticated users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::post('/2fa/setup', [ProfileController::class, 'setupTwoFactor'])->name('2fa.setup');
        Route::post('/2fa/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('2fa.confirm');
        Route::post('/2fa/disable', [ProfileController::class, 'disableTwoFactor'])->name('2fa.disable');
    });

    // Logout 
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});