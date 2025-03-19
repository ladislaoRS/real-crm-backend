<?php

use App\Http\Controllers\Api\ContactApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\TokenAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Public API routes
Route::post('/login', [TokenAuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [TokenAuthController::class, 'logout']);

    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Contacts API endpoints
    Route::apiResource('contacts', ContactApiController::class);
    Route::put('/contacts/{contact}/restore', [ContactApiController::class, 'restore'])->name('contacts.restore');

    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardApiController::class, 'getStats']);
});
