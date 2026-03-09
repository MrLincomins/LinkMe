<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShortDomainController;
use App\Http\Controllers\Api\ShortLinkController;
use App\Http\Controllers\Api\ShortLinkPasswordController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('links', ShortLinkController::class);
    Route::apiResource('domains', ShortDomainController::class);

    Route::apiResource('links.passwords', ShortLinkPasswordController::class)
        ->except(['show']);

    Route::get('links/trashed', [ShortLinkController::class, 'trashed']);
    Route::post('links/{id}/restore', [ShortLinkController::class, 'restore']);
    Route::delete('links/{id}/force', [ShortLinkController::class, 'forceDestroy']);
    Route::get('links/{link}/stats', [ShortLinkController::class, 'stats']);

});


