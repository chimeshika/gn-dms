<?php

use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

// Browser APIs deliberately use Laravel's web middleware for sessions and CSRF.
Route::prefix('api')->group(function () {
    Route::get('/session', [SessionController::class, 'show']);
    Route::post('/login', [SessionController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/register', [SessionController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/locations', [SessionController::class, 'locations']);
    Route::post('/logout', [SessionController::class, 'logout'])->middleware('auth');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::get('/dashboard', [DirectoryController::class, 'dashboard']);
        Route::get('/metadata', [DirectoryController::class, 'metadata']);
        Route::get('/documents/{document}/download', [DirectoryController::class, 'download']);
        Route::post('/officers/{officer}/verify', [DirectoryController::class, 'verify']);
        Route::get('/records/{resource}', [DirectoryController::class, 'index']);
        Route::post('/records/{resource}', [DirectoryController::class, 'store']);
        Route::put('/records/{resource}/{id}', [DirectoryController::class, 'update']);
        Route::delete('/records/{resource}/{id}', [DirectoryController::class, 'destroy']);
        Route::get('/batches', [BatchController::class, 'index']);
        Route::post('/batches', [BatchController::class, 'store']);
        Route::get('/batches/{batch}', [BatchController::class, 'show']);
        Route::put('/batches/{batch}', [BatchController::class, 'update']);
        Route::delete('/batches/{batch}', [BatchController::class, 'destroy']);
        Route::post('/batches/{batch}/import', [BatchController::class, 'import']);
        Route::post('/batches/{batch}/generate', [BatchController::class, 'generate']);
        Route::get('/batches/{batch}/download', [BatchController::class, 'downloadBatch']);
        Route::get('/letters/{letter}', [BatchController::class, 'letter']);
        Route::put('/letters/{letter}', [BatchController::class, 'updateLetter']);
        Route::delete('/letters/{letter}', [BatchController::class, 'deleteLetter']);
        Route::post('/letters/{letter}/finalize', [BatchController::class, 'finalize']);
        Route::get('/letters/{letter}/pdf', [BatchController::class, 'pdf']);
    });
});
