<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MeasurementController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::delete('/{id}', [UserController::class, 'destroy']);

    // User's measurements
    Route::get('/{userId}/measurements', [MeasurementController::class, 'getUserMeasurements']);

    // User's alerts
    Route::get('/{userId}/alerts', [AlertController::class, 'getUserAlerts']);
    Route::get('/{userId}/alerts/stats', [AlertController::class, 'getAlertStats']);
    Route::post('/{userId}/alerts/mark-all-read', [AlertController::class, 'markAllAsRead']);

    // Device attachment/detachment for users
    Route::post('/{userId}/devices/{deviceId}/attach', [DeviceController::class, 'attachToUser']);
    Route::delete('/{userId}/devices/{deviceId}/detach', [DeviceController::class, 'detachFromUser']);
});

Route::prefix('devices')->group(function () {
    Route::get('/', [DeviceController::class, 'index']);
    Route::post('/', [DeviceController::class, 'store']);
    Route::get('/{id}', [DeviceController::class, 'show']);
    Route::delete('/{id}', [DeviceController::class, 'destroy']);
    Route::get('/{deviceId}/measurements', [MeasurementController::class, 'getDeviceMeasurements']);
});

Route::prefix('measurements')->group(function () {
    Route::post('/', [MeasurementController::class, 'store']);
});

Route::prefix('alerts')->group(function () {
    Route::post('/{alertId}/mark-read', [AlertController::class, 'markAsRead']);
});
