<?php

use Illuminate\Support\Facades\Route;

//Redirect always to Tickets app view

Route::get('/', function () {
    return view('tickets.index');
});

Route::get('/tickets', function () {
    return view('tickets.index');
});
