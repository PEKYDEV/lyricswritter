<?php

use App\Http\Controllers\Api\SongController;
use App\Http\Controllers\Api\SongVersionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('index'));
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::prefix('api')->group(function () {
        Route::apiResource('songs', SongController::class);

        Route::prefix('songs/{song}/versions')->group(function () {
            Route::get('/', [SongVersionController::class, 'index']);
            Route::post('/', [SongVersionController::class, 'store']);
            Route::post('/{version}/restore', [SongVersionController::class, 'restore']);
        });
    });
});
