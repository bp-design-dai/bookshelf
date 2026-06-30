<?php

use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return 'ログイン成功';
})->middleware('auth');
