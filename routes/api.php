<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('/notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'show']);
    Route::get('/history', [NotificationController::class, 'history']);
    Route::post('/', [NotificationController::class, 'store']);
});
