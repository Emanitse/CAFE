<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

// Protected routes - require authentication + cache control
Route::middleware(['role'])->group(function () {
    
    // Home - accessible to all authenticated users (admin, staff)
    Route::get('/home', function () {
        return view('components.home'); 
    })->name('home');

    // Orders - accessible to all authenticated users
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

    // Reports - Admin only (example)
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports', function () {
            return view('components.reports'); 
        })->name('reports');
    });

    // Users Management - Admin ONLY (Staff explicitly denied)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', function () {
            return view('components.users'); 
        })->name('users');
    });

    // Logout - accessible to all authenticated users
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});