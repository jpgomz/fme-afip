<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AfipController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/afip/factura-c', [AfipController::class, 'emitirFactura']);

