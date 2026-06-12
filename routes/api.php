<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\StripePaymentController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\UpdateController;

Route::get('/campaigns', [CampaignController::class, 'index']);
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);

Route::get('/campaigns/{campaign}/updates', [UpdateController::class, 'index']);
Route::get('/campaigns/{campaign}/updates/{update}', [UpdateController::class, 'show']);

Route::post('/donation', [DonationController::class, 'store']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Routes d'authentification publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);

    Route::post('/campaigns/{campaign}/updates', [UpdateController::class, 'store']);
    Route::put('/campaigns/{campaign}/updates/{update}', [UpdateController::class, 'update']);
    Route::delete('/campaigns/{campaign}/updates/{update}', [UpdateController::class, 'destroy']);

    Route::post('/stripe/payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
    Route::get('/donations', [DonationController::class, 'index']);
    Route::post('/donations', [DonationController::class, 'store']);
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

    Route::get('/admin/financial-report', [FinancialReportController::class, 'export']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
