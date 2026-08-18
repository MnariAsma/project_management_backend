<?php

use App\Http\Controllers\GithubAuthController;

Route::prefix('auth/github')->group(function () {
    Route::get('/redirect', [GithubAuthController::class, 'redirect']);
    Route::get('/callback', [GithubAuthController::class, 'callback']);
});
