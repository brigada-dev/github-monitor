<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', function () {
    return \Illuminate\Support\Facades\Auth::check() ? to_route('repositories') : to_route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/repositories', [PageController::class,'repositories'])->name('repositories');
    Route::get('/commits/{owner}/{repo_name}', [PageController::class,'commits'])->name('commits');
});
