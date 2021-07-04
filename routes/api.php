<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MollieController;
use App\Http\Controllers\PlazaCamController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Plazacam submission
Route::put('plazacam/{user}/{image}', [PlazaCamController::class, 'store'])
    ->middleware('signed')
    ->name('plazacam.store');

// Plazacam viewing via API
Route::get('plazacam/{user}/{image}', [PlazaCamController::class, 'api'])
    ->middleware('signed')
    ->name('plazacam.view');

// Register API for Stripe endpoints
Route::stripeWebhooks('payments/stripe/handle');

// Register Telegram webhooks
Route::post('/bots/telegram', [TelegramBotController::class, 'handle'])->name('bots.telegram');

// Register Mollie webhook URL
Route::post('/mollie/hooks/activities', [MollieController::class, 'activity'])->name('webhooks.activities');
Route::post('/mollie/hooks/shop', [MollieController::class, 'shop'])->name('webhooks.shop');
