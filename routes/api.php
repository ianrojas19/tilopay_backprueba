<?php

use App\Http\Controllers\TicketController;
use App\Http\Middleware\CorrelationId;
use App\Http\Middleware\UserIdentity;
use Illuminate\Support\Facades\Route;

Route::middleware([CorrelationId::class, UserIdentity::class])->group(function () {
    Route::apiResource('tickets', TicketController::class);
});
