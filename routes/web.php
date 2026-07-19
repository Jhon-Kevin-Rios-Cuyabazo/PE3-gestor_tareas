<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint de la API (en web.php por simplicidad)
Route::get('/api/tasks', [TaskController::class, 'index']);
