<?php

use App\Http\Controllers\GithubAuthController;
use App\Http\Controllers\GithubRepositoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TokenController;

Route::prefix('auth/github')->group(function () {
    Route::get('/redirect', [GithubAuthController::class, 'redirect']);
    Route::get('/callback', [GithubAuthController::class, 'callback']);
});

Route::prefix('auth')->group(function () {
    Route::post('/refresh', [TokenController::class, 'refresh'])->middleware(['auth:sanctum', 'abilities:issue-access-token']);
    Route::post('/logout', [TokenController::class, 'logout'])->middleware(['auth:sanctum', 'abilities:access-api']);


});

Route::middleware(['auth:sanctum', 'abilities:access-api'])->group(function () {
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
    });
    Route::get('/github/repositories', [GithubRepositoryController::class, 'index']);

});


