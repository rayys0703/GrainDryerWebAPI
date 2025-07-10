<?php

use App\Http\Controllers\Auth\AuthController; // Tambahkan ini
use App\Http\Controllers\Api\PredictionController;
use App\Http\Controllers\Api\GrainTypeController;
use App\Http\Controllers\Api\TrainingDataController;
use App\Http\Controllers\Api\RealtimeDataController;
use App\Http\Controllers\Api\DryingProcessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route untuk otentikasi (Tidak Terproteksi)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route yang membutuhkan otentikasi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']); // Tambahkan route logout

    // Route::apiResource('grain-types', App\Http\Controllers\Api\GrainTypeController::class);
    Route::apiResource('drying-processes', App\Http\Controllers\Api\DryingProcessController::class);
    
    // Anda bisa menambahkan resource lain yang terproteksi di sini
    // Contoh:
    // Route::apiResource('grain-types', App\Http\Controllers\GrainTypeController::class);
    // Route::apiResource('drying-processes', App\Http\Controllers\DryingProcessController::class);
    // ... dan seterusnya untuk tabel-tabel lainnya
});

// Route::apiResource('grain-types', GrainTypeController::class);
Route::get('/grain-types', [GrainTypeController::class, 'index']);

Route::post('/training-data/store', [TrainingDataController::class, 'store']);
// Route::get('/training-data', [TrainingDataController::class, 'index']);
Route::get('/dataset', [TrainingDataController::class, 'index']);

Route::get('/realtime-data', [RealtimeDataController::class, 'index']);
// Route::get('/prediction/check-active', [PredictionController::class, 'checkActiveProcess']);
Route::post('/prediction/start', [PredictionController::class, 'startPrediction']);
Route::post('/prediction/stop', [PredictionController::class, 'stopPrediction']);
Route::post('/prediction/receive', [PredictionController::class, 'receivePrediction']);
// Route::get('/grain-types', [PredictionController::class, 'getGrainTypes']);
// Route::get('/sensor-data', [PredictionController::class, 'getSensorData']);

// History Drying Process
Route::get('/drying-history', [DryingProcessController::class, 'getHistory']);
Route::get('/drying-process/{processId}', [DryingProcessController::class, 'getProcessDetails']);