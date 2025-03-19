<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/dashboard', function () {
    dd(Session::all());
});
