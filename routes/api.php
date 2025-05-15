<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\UnavailabilityController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserProfileController::class, 'login']);
Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/users', [UserProfileController::class, 'index'])->name('users.index');
Route::get('/unavailability', [UnavailabilityController::class, 'index'])->name('unavail.index');

Route::get('/users/role/{id}', [UserProfileController::class, 'show'])->name('users.show');

Route::post('/users', [UserProfileController::class, 'store'])->name('users.store');


Route::prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::post('/', [LocationController::class, 'store']);
    Route::get('{id}', [LocationController::class, 'show']);
    Route::put('{id}', [LocationController::class, 'update']);
    Route::delete('{id}', [LocationController::class, 'destroy']);
});