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

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

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

    // Menu Management - Admin only
    Route::middleware('role:admin')->prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::put('/{id}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{id}', [MenuController::class, 'destroy'])->name('destroy');
    });

    // Categories Management - Admin only
    Route::middleware('role:admin')->prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // Reports - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/history', [ReportController::class, 'getOrderHistory'])->name('reports.history');
        Route::get('/reports/categories', [ReportController::class, 'getCategories'])->name('reports.categories');
        Route::get('/reports/items', [ReportController::class, 'getItems'])->name('reports.items');
        Route::post('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.exportPdf');
        });

    
    // Users Management - Admin & Superadmin
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/list', [UserController::class, 'getUsers'])->name('list');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle');
    });

    // Profile Settings - accessible to all authenticated users
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
    });

    // Logout 
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});