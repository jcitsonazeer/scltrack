<?php

use App\Http\Controllers\AdminAndDriverController;
use App\Http\Controllers\ActiveTripController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverPortalController;
use App\Http\Controllers\LiveVehicleLocationController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SchoolSelectionController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentRouteAssignmentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleLocationHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
})->middleware('admin.login');

Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.store');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('select-school', [SchoolSelectionController::class, 'index'])->name('app.select-school');
Route::post('select-school', [SchoolSelectionController::class, 'select'])->name('app.select-school.store');
Route::get('login-choice', [SchoolSelectionController::class, 'loginChoice'])->name('app.login-choice');

Route::get('parent/login', [ParentPortalController::class, 'showLogin'])->name('app.parent.login');
Route::post('parent/login', [ParentPortalController::class, 'login'])->name('app.parent.login.store');
Route::get('parent/dashboard', [ParentPortalController::class, 'dashboard'])->name('app.parent.dashboard');
Route::post('parent/logout', [ParentPortalController::class, 'logout'])->name('app.parent.logout');

Route::get('driver/login', [DriverPortalController::class, 'showLogin'])->name('app.driver.login');
Route::post('driver/login', [DriverPortalController::class, 'login'])->name('app.driver.login.store');
Route::get('driver/dashboard', [DriverPortalController::class, 'dashboard'])->name('app.driver.dashboard');
Route::post('driver/logout', [DriverPortalController::class, 'logout'])->name('app.driver.logout');

Route::get('driver/stops', [DriverPortalController::class, 'stops'])->name('app.driver.stops');
Route::post('driver/stops', [DriverPortalController::class, 'storeStop'])->name('app.driver.stops.store');
Route::post('driver/stops/{stopId}/location', [DriverPortalController::class, 'updateStopLocation'])->name('app.driver.stops.location');

/*
 * module.permission resolves the module and the action from each route name,
 * so index / show are read access while store / update / destroy require the
 * matching write permission.
 *
 * driver.scope is a no-op for every role except "cab drivers". For them it
 * blocks records in the driver scoped modules that do not belong to them.
 */
Route::middleware(['admin.login', 'module.permission'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::get('lookups/class-sections', [LookupController::class, 'classSections'])->name('lookups.class-sections');
    Route::get('lookups/students', [LookupController::class, 'students'])->name('lookups.students');
    Route::get('lookups/routes', [LookupController::class, 'routes'])->name('lookups.routes');
    Route::get('lookups/stops', [LookupController::class, 'stops'])->name('lookups.stops');

    Route::resource('parents', ParentController::class);
    Route::resource('students', StudentController::class);
    Route::resource('class-sections', ClassSectionController::class)
        ->parameters(['class-sections' => 'classSection']);
    Route::resource('vehicles', VehicleController::class);
    Route::resource('admin-and-drivers', AdminAndDriverController::class)
        ->parameters(['admin-and-drivers' => 'adminAndDriver']);
    Route::resource('vehicle-routes', RouteController::class)
        ->parameters(['vehicle-routes' => 'route']);
    Route::resource('stops', StopController::class);
    Route::resource('student-route-assignments', StudentRouteAssignmentController::class);
    Route::resource('active-trips', ActiveTripController::class)
        ->parameters(['active-trips' => 'activeTrip']);
    Route::resource('live-vehicle-locations', LiveVehicleLocationController::class)
        ->parameters(['live-vehicle-locations' => 'liveVehicleLocation']);
    Route::resource('vehicle-location-history', VehicleLocationHistoryController::class)
        ->parameters(['vehicle-location-history' => 'vehicleLocationHistory']);
})->middleware('driver.scope');
