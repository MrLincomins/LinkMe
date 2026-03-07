<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RedirectController::class, 'home']);

Route::get('/admin', fn () => view('admin.index'));

Route::match(['get', 'post'], '/{code}', [RedirectController::class, 'handle'])
    ->where('code', '[a-zA-Z0-9_-]+');
