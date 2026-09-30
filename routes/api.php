<?php

use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\ParentAuthController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::apiResource('parents', ParentController::class)->names('api.parents');
Route::apiResource('students', StudentController::class)->names('api.students');

Route::prefix('v1')->group(function () {
    Route::apiResource('parents', ParentController::class)->names('api.v1.parents');
    Route::apiResource('students', StudentController::class)->names('api.v1.students');

    Route::get('schools', [SchoolController::class, 'index'])->name('api.v1.schools.index');
    Route::get('schools/{schoolId}', [SchoolController::class, 'show'])->name('api.v1.schools.show');

    Route::post('parent/login', [ParentAuthController::class, 'login'])->name('api.v1.parent.login');
    Route::get('parent/me', [ParentAuthController::class, 'me'])->name('api.v1.parent.me');
    Route::post('parent/logout', [ParentAuthController::class, 'logout'])->name('api.v1.parent.logout');

    Route::post('driver/login', [DriverAuthController::class, 'login'])->name('api.v1.driver.login');
    Route::get('driver/me', [DriverAuthController::class, 'me'])->name('api.v1.driver.me');
    Route::post('driver/logout', [DriverAuthController::class, 'logout'])->name('api.v1.driver.logout');
});
