<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [SessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [SessionController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::get('/', MapController::class)->name('dashboard');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});
