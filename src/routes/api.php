<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Thirdparty\JokerController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(JokerController::class)->group(function () {
    Route::post('/joker/authenticate', 'auth');
    Route::post('/joker/balance', 'getBalance');
    Route::post('/joker/bet', 'bet');
    Route::post('/joker/cancel-bet', 'cancelBet');
    Route::post('/joker/settle-bet', 'settle');
    Route::post('/joker/get-hash', 'getHash');
});