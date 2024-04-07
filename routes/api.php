<?php

use App\Http\Controllers\Api\v1\CounterTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\TicketController;
use App\Http\Controllers\HandlePaymentCounterNotificationController;
use App\Http\Controllers\HandlePaymentNotificationController;

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

Route::post('ticket/buy', [TicketController::class, 'buy']);

# URL yang akan di Hit Midtrans
Route::post('midtrans/notif-hook', HandlePaymentNotificationController::class);


Route::post('payment-alfa', CounterTransactionController::class);
Route::post('verify-payment', HandlePaymentCounterNotificationController::class);
