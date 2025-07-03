<?php

use App\Http\Controllers\Api\ChangePasswordController;
use App\Http\Controllers\Api\CompanyMasterController;
use App\Http\Controllers\Api\DownloadRosterPdf;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\LocationSalesController;
use App\Http\Controllers\Api\LocationUser;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\RosterAttendanceController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\RosterTimesheetController;
use App\Http\Controllers\Api\UnavailabilityController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserProfileController::class, 'login']);
Route::post('/forgotPassword', [ForgotPasswordController::class, 'forgotPassword']);
Route::get('/locationSales/location/{id?}', [LocationSalesController::class, 'index']);
Route::put('/locationSales/{id}', [LocationSalesController::class, 'update']);

Route::put('/notifications/{id}', [UserProfileController::class, 'markAsRead']);
Route::get('rosterfetch', [RosterController::class, 'index']);
Route::get('rosterfetch/{location_id?}/{loginId?}', [RosterController::class, 'getRosterByLoginId']);
Route::post('rosterWeekftch', [RosterController::class, 'getRosterWeekData']);
Route::get('rosterWeekDay', [RosterController::class, 'getWeekDatesId']);
Route::POST('porstRoster', [RosterController::class, 'postRoster']);
Route::post('/updateSingleRoster', [RosterController::class, 'updateSingleUserRoster']);
Route::post('pubUnpub/{weekId}/{locationId}',[RosterController::class, 'pubUnpub'] );
Route::delete('rosterDelete',[RosterController::class, 'delete']);
Route::delete('rosterWeekDelete/all',[RosterController::class, 'allweekdelete']);
Route::get('dashboardCards',[RosterController::class, 'dashboardCards']);
Route::get('dashboardData',[RosterController::class, 'dashboardData']);
Route::post('/attendance/log', [RosterAttendanceController::class, 'logAction']);
Route::get('/attendance/logs', [RosterAttendanceController::class, 'getActions']);
Route::post('/generatetimesheet', [RosterTimesheetController::class, 'store']);
Route::get('/timesheet/weekly-summary', [RosterTimesheetController::class, 'getWeeklySummary']);

// Company Master Routes
Route::prefix('/company')->group(function () {
    Route::get('/', [CompanyMasterController::class, 'getCompanyDetails']);
    Route::post('/', [CompanyMasterController::class, 'updateCompanyDetails']);
    Route::post('/create', [CompanyMasterController::class, 'createCompany']);
    Route::put('/{id}', [CompanyMasterController::class, 'updateCompany']);
    Route::delete('/{id}', [CompanyMasterController::class, 'deleteCompany']);
    Route::get('/{id}', [CompanyMasterController::class, 'showCompany']);
});


Route::middleware('apiauth')->group(function () {
    Route::get('/notifications', [UserProfileController::class, 'getNotifications']);
    Route::post('/notifications', [UserProfileController::class, 'markAllAsRead']);

    Route::post('/logout', [UserProfileController::class, 'logout']);
    Route::post('/changePassword', [ChangePasswordController::class, 'changePassword']);

    Route::prefix('/users')->group(function () {
        Route::get('/manager/{id?}', [UserProfileController::class, 'forManagers']);
        Route::get('/{id?}', [UserProfileController::class, 'index']);
        Route::post('/', [UserProfileController::class, 'store'])->name('users.store');
        Route::post('{id}', [UserProfileController::class, 'update']);
        Route::delete('{id}', [UserProfileController::class, 'destroy']);
        Route::get('/role/{id}', [UserProfileController::class, 'show'])->name('users.show');
        Route::get('/login/{location_id?}', [UserProfileController::class, 'getUsersCreatedBy']);
        Route::post('/profile/{id}/status', [UserProfileController::class, 'updateStatus']);
    });

    Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');         // List all roles
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');        // Create a new role
    Route::get('/roles/{id}', [RolesController::class, 'show'])->name('roles.show');      // Get a single role
    Route::put('/roles/{id}', [RolesController::class, 'update'])->name('roles.update');  // Update a role
    Route::delete('/roles/{id}', [RolesController::class, 'destroy'])->name('roles.destroy'); // Delete a role

    Route::prefix('/unavailability')->group(function () {
        Route::get('/login/{id?}', [UnavailabilityController::class, 'index'])->name('unavail.index');
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
        // Route::get('/{location_id}/users', [LocationController::class, 'getUsersByLocation']);

        Route::post('/{locationId}/users', [LocationController::class, 'postUsersinLocation']);
        Route::put('/{locationId}/users/', [LocationController::class, 'updateUsersLocation']);
        Route::delete('/{locationId}/users/', [LocationController::class, 'deleteUserFromLocation']);
        Route::get('/{locationId}/active-users', [LocationUser::class, 'getActiveUsersByLocation']);

    });

    Route::get('/generatepdf', [DownloadRosterPdf::class, 'downloadRosterPDF']);
    Route::post('/timesheet/download-pdf', [RosterTimesheetController::class, 'downloadTimesheetPdf']);

});
