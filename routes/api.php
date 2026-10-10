<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'application' => 'HRIS Enterprise',
        'database' => 'PostgreSQL 18',
        'framework' => 'Laravel 13',
        'timestamp' => now()->toIso8601String(),
    ]);
});
