<?php

use App\Http\Controllers\Api\ChangePasswordController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\LocationSalesController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\UnavailabilityController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

    Route::post('/login', [UserProfileController::class, 'login']);
    Route::post('/  ', [ForgotPasswordController::class, 'forgotPassword']);
    Route::post('/login', [UserProfileController::class, 'login']);
    Route::post('/forgotPassword', [ForgotPasswordController::class, 'forgotPassword']);
    Route::get('/locationSales/location/{id?}', [LocationSalesController::class, 'index']);
    Route::put('/locationSales/{id}', [LocationSalesController::class, 'update']);

Route::middleware('apiauth')->group(function () {
    Route::post('/logout', [UserProfileController::class, 'logout']);
    Route::post('/changePassword', [ChangePasswordController::class, 'changePassword']);

    Route::prefix('/users')->group(function () {
        Route::get('/{id?}', [UserProfileController::class, 'index']);
        Route::post('/', [UserProfileController::class, 'store'])->name('users.store');
        Route::post('{id}', [UserProfileController::class, 'update']);
        Route::delete('{id}', [UserProfileController::class, 'destroy']);
        Route::get('/role/{id}', [UserProfileController::class, 'show'])->name('users.show');
        Route::get('/login/{loginId?}', [UserProfileController::class, 'getUsersCreatedBy']);
        Route::post('/filter', [UserProfileController::class, 'filterEmployees'])->name('users.filter');

    });

    Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');

    Route::prefix('/unavailability')->group(function () {
        Route::get('/', [UnavailabilityController::class, 'index'])->name('unavail.index');
        Route::post('/{id}', [UnavailabilityController::class, 'store']);
        Route::get('{id}', [UnavailabilityController::class, 'show']);
        Route::put('{id}', [UnavailabilityController::class, 'update']);
        Route::delete('{id}', [UnavailabilityController::class, 'destroy']);
    });

    Route::prefix('locations')->group(function () {
        Route::get('/', [LocationController::class, 'index']);
        Route::get('{id}', [LocationController::class, 'show']);
        Route::post('/', [LocationController::class, 'store']);
        Route::put('{id}', [LocationController::class, 'update']);
        Route::delete('{id}', [LocationController::class, 'destroy']);
        // Fetch users by location ID
        Route::get('/{locationId}/role/{roleId}', [LocationController::class, 'getRolesByLocationId']);
        Route::get('/{location_id}/users', [LocationController::class, 'getUsersByLocation']);
    });
});
