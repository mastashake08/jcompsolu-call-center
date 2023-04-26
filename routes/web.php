<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('/menu', 'App\Http\Controllers\MenuController@generateMenuTwiml');
Route::get('/stripe/return', 'App\Http\Controllers\StripeConnectController@finishOnboarding');
Route::get('/get-paid', 'App\Http\Controllers\StripeConnectController@createExpressAccount');
