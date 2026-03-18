<?php

use App\Http\Controllers\GeoBase\WebhookController;
use App\Http\Middleware\VerifyGeoBaseWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/geobase', [WebhookController::class, 'handle'])
    ->middleware(VerifyGeoBaseWebhook::class)
    ->name('webhooks.geobase');
