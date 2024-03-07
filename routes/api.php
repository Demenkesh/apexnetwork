<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Auth::routes(['verify' => true]);

Route::prefix('v1/auth/admin')->group(function () {

    Route::controller(App\Http\Controllers\API\adminauthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('sendResetLinkEmail', 'sendResetLinkEmail');
        Route::post('password/reset', 'reset')->name('password-reset');
        Route::get('/email/verify', 'show')->name('verify-email');
        Route::middleware(['auth:sanctum', 'auth', 'verified', 'isAPIAdmin'])->group(function () {
            Route::post('logout', 'logout');
        });
    });

    // admin route
    Route::middleware(['auth:sanctum', 'auth', 'verified', 'isAPIAdmin'])->group(function () {
        // User management routes
        Route::controller(App\Http\Controllers\API\userController::class)->group(function () {
            Route::get('/users', 'index');
            Route::post('/users', 'store');
            Route::get('/users/{id}', 'show');
            Route::put('/users/{id}', 'update');
            Route::delete('/users/{id}', 'destroy');
        });
    });
});

//USER ROUTE
Route::prefix('v1/auth/user')->group(function () {

    Route::controller(App\Http\Controllers\API\userauthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('sendResetLinkEmail', 'sendResetLinkEmail');
        Route::post('password/reset', 'reset')->name('password-reset');
        Route::get('/email/verify', 'show')->name('verify-email');
        Route::middleware(['auth:sanctum', 'auth', 'verified'])->group(function () {
            Route::post('logout', 'logout');
        });
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
