<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Callback SSO Supervision : reçoit ?sup_token=, valide, connecte et redirige vers le dashboard
Route::get('/sso-callback', function () {
    return view('sso-callback');
});
