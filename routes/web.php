<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return success('aaaaa helllo how are you kartik
    !');
    return view('welcome');
});
