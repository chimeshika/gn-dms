<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

Route::prefix('register')->name('register.')->group(function () {
    Route::get('/', [PublicRegistrationController::class, 'create'])->name('create');
    Route::post('/', [PublicRegistrationController::class, 'store'])->name('store');
});

Route::get('/api/ds-divisions', [PublicRegistrationController::class, 'dsDivisions']);
Route::get('/api/gn-divisions', [PublicRegistrationController::class, 'gnDivisions']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'permission:manage batches|generate letters'])->group(function () {
    Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');
    Route::get('/letters/create', [LetterController::class, 'create'])->name('letters.create');
    Route::post('/letters', [LetterController::class, 'store'])->name('letters.store');
    Route::get('/letters/batches/{batch}', [LetterController::class, 'show'])->name('letters.show');
    Route::post('/letters/batches/{batch}/import', [LetterController::class, 'import'])->name('letters.import');
    Route::post('/letters/batches/{batch}/generate', [LetterController::class, 'generate'])->name('letters.generate');
    Route::get('/letters/batches/{batch}/pdf', [LetterController::class, 'bulkPdf'])->name('letters.bulk.pdf');
    Route::get('/letters/{letter}/edit', [LetterController::class, 'editLetter'])->name('letters.edit');
    Route::put('/letters/{letter}', [LetterController::class, 'updateLetter'])->name('letters.update');
    Route::delete('/letters/{letter}', [LetterController::class, 'destroy'])->name('letters.destroy');
    Route::post('/letters/{letter}/finalize', [LetterController::class, 'finalize'])->name('letters.finalize');
    Route::get('/letters/{letter}/pdf', [LetterController::class, 'pdf'])->name('letters.pdf');
    Route::get('/letters/{letter}/preview', [LetterController::class, 'previewPdf'])->name('letters.preview');
});
