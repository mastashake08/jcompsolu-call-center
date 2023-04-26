<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/menu', 'App\Http\Controllers\MenuController@handleMenu');
Route::post('/sms', 'App\Http\Controllers\SmsController@handleIncomingSms');
Route::post('/twilio/incoming/payment/{num}/value/{value}', 'App\Http\Controllers\MenuController@pay');
Route::post('/send-money-start','App\Http\Controllers\MenuController@startSendMoney');
Route::post('/send-money-start-confirm','App\Http\Controllers\MenuController@confirmStartSendMoney');
Route::post('/send-money-get-funds-confirm','App\Http\Controllers\MenuController@confirmGetCardInfo');
Route::post('/send-money-get-funds','App\Http\Controllers\MenuController@getCardInfo');
