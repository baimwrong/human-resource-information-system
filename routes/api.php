<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'application' => config('app.name', 'HRIS Enterprise'),
        'database' => config('database.default', 'pgsql'),
        'timestamp' => now()->toIso8601String(),
    ]);
});
