<?php

use App\Http\Controllers\Auth\WebAuthController; // Tambahkan ini
use App\Http\Controllers\PredictionFormController;
use App\Http\Controllers\Web\ValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/prediction', [PredictionFormController::class, 'show'])->name('prediction.form');

// Tampilan Login
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post'); // Route untuk submit form login

// Tampilan Register
Route::get('/register', function () {
    return view('auth.register');
})->name('register');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.post'); // Route untuk submit form register

// Route Logout
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth'); // Middleware 'auth' agar hanya user terautentikasi bisa logout

// Contoh Dashboard (Anda harus membuatnya)
Route::middleware('auth:sanctum')->group(function () {
    // routes/web.php
Route::get('/realtime-monitor', fn() => view('realtime_monitor'))->name('realtime.monitor');


    Route::get('/dashboard', function () {
        return "Selamat datang di Dashboard!";
    })->name('dashboard');

    // Contoh dashboard spesifik role
    Route::get('/admin/dashboard', function () {
        return "Selamat datang Admin!";
    })->name('admin.dashboard');

    Route::get('/petani/dashboard', function () {
        return "Selamat datang Petani!";
    })->name('petani.dashboard');

    Route::get('/operator/dashboard', function () {
        return "Selamat datang Operator!";
    })->name('operator.dashboard');

    Route::resource('grain-types', App\Http\Controllers\Web\GrainTypeController::class);
    Route::resource('drying-processes', App\Http\Controllers\Web\DryingProcessController::class);
});


// Redirect root ke login jika belum login, atau ke dashboard jika sudah
Route::get('/', function () {
    if (Auth::check()) {
        switch (Auth::user()->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'petani':
                return redirect()->route('petani.dashboard');
            case 'operator':
                return redirect()->route('operator.dashboard');
            default:
                return redirect()->route('dashboard');
        }
    }
    return redirect()->route('login');
});

Route::get('/validation/create', [ValidationController::class, 'create'])->name('validation.create');
Route::post('/validation', [ValidationController::class, 'store'])->name('validation.store');
Route::get('/validation', [ValidationController::class, 'index'])->name('validation.index');
Route::post('/validation/{processId}', [ValidationController::class, 'validateProcess'])->name('validation.process');
Route::get('/validation/{processId}', [ValidationController::class, 'show'])->name('validation.show');

Route::get('/dataset', [ValidationController::class, 'indexDataset'])->name('dataset.index');
Route::get('/dataset/{processId}', [ValidationController::class, 'showDataset'])->name('dataset.show');