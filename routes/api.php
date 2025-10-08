<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InternalVerificationController;
use App\Http\Controllers\Api\TokenController;

// Public routes - Login to access dashboard
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Protected routes - Need to login first to manage tokens
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Token Management Routes
    Route::post('/tokens/create', [TokenController::class, 'create']);
    Route::get('/tokens', [TokenController::class, 'index']);
    Route::delete('/tokens/{id}', [TokenController::class, 'destroy']);
    Route::delete('/tokens', [TokenController::class, 'destroyAll']);
    
    // API Routes - Can be accessed with permanent token
    Route::get('/verifications', [InternalVerificationController::class, 'getAll']);
    Route::get('/verifications/{id}', [InternalVerificationController::class, 'show']);
    Route::post('/verifications', [InternalVerificationController::class, 'store']);
    Route::put('/verifications/{id}', [InternalVerificationController::class, 'update']);
    Route::delete('/verifications/{id}', [InternalVerificationController::class, 'destroy']);
});