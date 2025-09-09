<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Api\PredictionController;
use App\Http\Controllers\Api\GrainTypeController;
use App\Http\Controllers\Api\TrainingDataController;
use App\Http\Controllers\Api\RealtimeDataController;
use App\Http\Controllers\Api\DryingProcessController;
use App\Http\Controllers\Api\SensorDevicesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Prediction APIs (wajib login)
    Route::post('/prediction/start', [PredictionController::class, 'startPrediction']);
    Route::post('/prediction/stop', [PredictionController::class, 'stopPrediction']);
    Route::post('/prediction/receive', [PredictionController::class, 'receivePrediction']);

    // Bed Dryers
    Route::get('/bed-dryers', [AuthController::class, 'myBedDryers']);

    Route::get('/drying-history', [DryingProcessController::class, 'getHistory']);
    Route::get('/drying-process/{processId}', [DryingProcessController::class, 'getProcessDetails']);
    Route::post('/drying-process/validate', [DryingProcessController::class, 'validateProcess']);

    Route::get('/sensor-devices', [SensorDevicesController::class, 'index']);
    Route::post('/sensor-devices/{device}/reset-delete', [SensorDevicesController::class, 'resetAndDelete']);
});

Route::post('/sensor-devices/new', [SensorDevicesController::class, 'newSensor']);

Route::get('/grain-types', [GrainTypeController::class, 'index']);

Route::get('/dataset', [TrainingDataController::class, 'index']);

Route::get('/realtime-data', [RealtimeDataController::class, 'index']);
Route::get('/dashboard-data', [RealtimeDataController::class, 'dashboardData']);

Route::post('/prediction/start', [PredictionController::class, 'startPrediction']);
Route::post('/prediction/stop', [PredictionController::class, 'stopPrediction']);
Route::post('/prediction/receive', [PredictionController::class, 'receivePrediction']);
