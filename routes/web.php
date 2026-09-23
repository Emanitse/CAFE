<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::get('/home', function () {
    return view('components.home'); 
});

Route::get('/orders', function () {
    return view('components.orders'); 
});

Route::get('/menu', function () {
    return view('components.menu'); 
});

Route::get('/categories', function () {
    return view('components.categories'); 
});

Route::get('/payments', function () {
    return view('components.payments'); 
});

Route::get('/reports', function () {
    return view('components.reports'); 
});

Route::get('/users', function () {
    return view('components.users'); 
});

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');