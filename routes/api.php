<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\UnavailabilityController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserProfileController::class, 'login']);

Route::middleware('apiauth')->group(function () {
    Route::post('/logout', [UserProfileController::class, 'logout']);
    Route::prefix('/users')->group(function () {
    Route::get('/', [UserProfileController::class, 'index']);
    Route::post('/users', [UserProfileController::class, 'store'])->name('users.store');
    Route::get('/users/role/{id}', [UserProfileController::class, 'show'])->name('users.show');
    Route::put('{id}', [UserProfileController::class, 'update']);
    Route::delete('{id}', [UserProfileController::class, 'destroy']);
});
});

Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');


Route::prefix('/unavailability')->group(function () {
    Route::get('/unavailability', [UnavailabilityController::class, 'index'])->name('unavail.index');
    Route::post('/', [UnavailabilityController::class, 'store']);
    Route::get('{id}', [UnavailabilityController::class, 'show']);
    Route::put('{id}', [UnavailabilityController::class, 'update']);
    Route::delete('{id}', [UnavailabilityController::class, 'destroy']);
});

Route::prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::post('/', [LocationController::class, 'store']);
    Route::get('{id}', [LocationController::class, 'show']);
    Route::put('{id}', [LocationController::class, 'update']);
    Route::delete('{id}', [LocationController::class, 'destroy']);
});
