<?php

use App\Http\Controllers\API\V1\HoldersLoggerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/process-logs', [HoldersLoggerController::class, 'processLogs']);
