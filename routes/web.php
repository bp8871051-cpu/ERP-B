<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(env('FRONTEND_URL', 'http://localhost:5173'));
});

Route::get('/login', function () {
    return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/login');
});

Route::fallback(function () {
    $path = request()->path();
    return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/' . ltrim($path, '/'));
});
