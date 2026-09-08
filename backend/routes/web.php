<?php

use Illuminate\Support\Facades\Route;

// Keep browser API endpoints on the web middleware stack: sessions and CSRF.
require __DIR__.'/api.php';

Route::get('/', fn () => response()->json([
    'application' => 'GN-DMS Laravel API',
    'frontend' => 'Run the React app on http://127.0.0.1:5173.',
]));
