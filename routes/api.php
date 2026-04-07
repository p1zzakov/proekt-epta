<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\RespondController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

// Статус системы
Route::get('/status', StatusController::class);

// Боты — CRUD
Route::apiResource('bots', BotController::class);
Route::post('bots/{bot}/reset-cooldown', [BotController::class, 'resetCooldown']);

// Аккаунты — пул Twitch аккаунтов
Route::get('accounts/stats', [AccountController::class, 'stats']);
Route::post('accounts/{account}/validate', [AccountController::class, 'checkToken']);
Route::apiResource('accounts', AccountController::class)->only(['index', 'store', 'show', 'destroy']);

// Главный эндпоинт — ответ бота на фразу стримера
Route::post('/respond', RespondController::class);