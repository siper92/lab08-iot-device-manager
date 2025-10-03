<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminDeviceController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserDeviceController;
use App\Http\Controllers\UserMeasurementController;
use App\Http\Controllers\DeviceMeasurementController;
use Illuminate\Support\Facades\Route;

// Admin public routes
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Admin protected routes
Route::middleware('jwt.admin')->group(function () {
    Route::post('/admin/users', [AdminUserController::class, 'create']);
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'delete']);
    Route::post('/admin/devices', [AdminDeviceController::class, 'create']);
    Route::delete('/admin/devices/{id}', [AdminDeviceController::class, 'delete']);
    Route::post('/admin/devices/{deviceId}/attach', [AdminDeviceController::class, 'attach']);
    Route::delete('/admin/devices/{deviceId}/detach', [AdminDeviceController::class, 'detach']);
});

// User public routes
Route::post('/login', [UserAuthController::class, 'login']);

// User protected routes
Route::middleware('jwt.user')->group(function () {
    Route::post('/users/{userId}/devices/{deviceId}/attach', [UserDeviceController::class, 'attach']);
    Route::delete('/users/{userId}/devices/{deviceId}/detach', [UserDeviceController::class, 'detach']);
    Route::get('/users/{userId}/measurements', [UserMeasurementController::class, 'getMeasurements']);
    Route::get('/users/{userId}/alerts', [UserMeasurementController::class, 'getAlerts']);
});

// Device protected routes
Route::middleware('jwt.device')->group(function () {
    Route::post('/devices/{deviceId}/measurements', [DeviceMeasurementController::class, 'submit']);
});
