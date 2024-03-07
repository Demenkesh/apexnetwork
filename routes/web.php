<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('generate-swagger', function () {
    try {
        // Execute the SwaggerGenerate command
        Artisan::call('cache:clear');
        Artisan::call('l5-swagger:generate');

        return 'Cache cleared successfully and Swagger documentation generated successfully!';
    } catch (\Exception $e) {
        // Handle exceptions and return an error message
        return 'Error generating Swagger documentation: ' . $e->getMessage();
    }
});

Route::get('/', [App\Http\Controllers\Api\adminauthController::class, 'home']);
