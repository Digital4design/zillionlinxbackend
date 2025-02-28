<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return success(' helllo how are you kartik g !');
    return view('welcome');
});
