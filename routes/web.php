<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BotController;
use App\Http\Controllers\Admin\BotTypeController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ProxyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\TestController;

// ─────────────────────────────────────────
// Админка
// ─────────────────────────────────────────
Route::get('/admin/login',  [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout',[AuthController::class, 'logout'])->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {

    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');

    // Боты
    Route::get('/bots',           [BotController::class, 'index'])->name('bots.index');
    Route::post('/bots',          [BotController::class, 'store'])->name('bots.store');
    Route::put('/bots/{bot}',     [BotController::class, 'update'])->name('bots.update');
    Route::delete('/bots/{bot}',  [BotController::class, 'destroy'])->name('bots.destroy');

    // Типы ботов
    Route::get('/bot-types',               [BotTypeController::class, 'index'])->name('bot-types.index');
    Route::post('/bot-types',              [BotTypeController::class, 'store'])->name('bot-types.store');
    Route::put('/bot-types/{botType}',     [BotTypeController::class, 'update'])->name('bot-types.update');
    Route::delete('/bot-types/{botType}',  [BotTypeController::class, 'destroy'])->name('bot-types.destroy');

    // Аккаунты
    Route::get('/accounts',                    [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts',                   [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{account}',          [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}',       [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::get('/accounts/import',                  [AccountController::class, 'importForm'])->name('accounts.import');
    Route::post('/accounts/import',                 [AccountController::class, 'import'])->name('accounts.import.post');
    Route::post('/accounts/bulk-check',             [AccountController::class, 'bulkCheck'])->name('accounts.bulk-check');
    Route::post('/accounts/{account}/toggle',       [AccountController::class, 'toggle'])->name('accounts.toggle');
    Route::post('/accounts/{account}/validate',     [AccountController::class, 'validateToken'])->name('accounts.validate');
    Route::post('/accounts/{account}/check-chat',   [AccountController::class, 'checkChat'])->name('accounts.check-chat');
    Route::post('/accounts/{account}/check-phone',  [AccountController::class, 'checkPhone'])->name('accounts.check-phone');
    Route::post('/accounts/bulk-phone-check',        [AccountController::class, 'bulkPhoneCheck'])->name('accounts.bulk-phone-check');

    // Прокси
    Route::get('/proxies',              [ProxyController::class, 'index'])->name('proxies.index');
    Route::post('/proxies',             [ProxyController::class, 'store'])->name('proxies.store');
    Route::delete('/proxies/{proxy}',   [ProxyController::class, 'destroy'])->name('proxies.destroy');
    Route::post('/proxies/import',      [ProxyController::class, 'import'])->name('proxies.import');

    // Пользователи
    Route::get('/users',             [UserController::class, 'index'])->name('users.index');
    Route::post('/users',            [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}',      [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',   [UserController::class, 'destroy'])->name('users.destroy');

    // Рассылка
    Route::get('/broadcast',    [BroadcastController::class, 'index'])->name('broadcast');
    Route::post('/broadcast',   [BroadcastController::class, 'send'])->name('broadcast.send');

    // Тарифы
    Route::get('/plans',             [PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans',            [PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}',      [PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}',   [PlanController::class, 'destroy'])->name('plans.destroy');

    // Настройки
    Route::get('/settings',  [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings',  [SettingsController::class, 'update'])->name('settings.update');

    // Почта
    Route::get('/mail',             [MailController::class, 'index'])->name('mail.index');
    Route::get('/mail/{id}',        [MailController::class, 'show'])->name('mail.show');
    Route::post('/mail/send',       [MailController::class, 'send'])->name('mail.send');
    Route::post('/mail/{id}/reply', [MailController::class, 'reply'])->name('mail.reply');

    // Стрим
    Route::get('/stream',    fn() => view('admin.stream.index'))->name('stream');
    Route::get('/chat-log',  fn() => view('admin.chat-log'))->name('chat-log');

    // Тесты
    Route::prefix('tests')->name('tests.')->group(function () {
        Route::get('/',              fn() => view('admin.tests.index'))->name('index');
        Route::post('/whisper',      [TestController::class, 'whisper'])->name('whisper');
        Route::get('/whisper-log',   [TestController::class, 'whisperLog'])->name('whisper.log');
        Route::post('/chat',         [TestController::class, 'chat'])->name('chat');
        Route::post('/follow-bots',  [TestController::class, 'followBots'])->name('follow');
        Route::get('/follow-status', [TestController::class, 'followStatus'])->name('follow.status');
        Route::post('/bot-chat/start',   [TestController::class, 'botChatStart'])->name('bot-chat.start');
        Route::post('/bot-chat/stop',    [TestController::class, 'botChatStop'])->name('bot-chat.stop');
        Route::get('/bot-chat/log',      [TestController::class, 'botChatLog'])->name('bot-chat.log');
        Route::get('/chat/live',         [TestController::class, 'chatLive'])->name('chat.live');
        Route::post('/chat/send',        [TestController::class, 'chatSend'])->name('chat.send');
        Route::post('/viewers/start',     [TestController::class, 'viewersStart'])->name('viewers.start');
        Route::post('/viewers/stop',      [TestController::class, 'viewersStop'])->name('viewers.stop');
        Route::get('/viewers/stats',      [TestController::class, 'viewersStats'])->name('viewers.stats');
    });
});

// ─────────────────────────────────────────
// Лендинг
// ─────────────────────────────────────────
Route::get('/', function () {
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('price')->get();
    return view('welcome', compact('plans'));
});

// ─────────────────────────────────────────
// Клиентская зона
// ─────────────────────────────────────────
Route::get('/register',  [\App\Http\Controllers\Auth\ClientAuthController::class, 'showRegister'])->name('client.register');
Route::post('/register', [\App\Http\Controllers\Auth\ClientAuthController::class, 'register'])->name('client.register.post');
Route::get('/login',     [\App\Http\Controllers\Auth\ClientAuthController::class, 'showLogin'])->name('client.login');
Route::post('/login',    [\App\Http\Controllers\Auth\ClientAuthController::class, 'login'])->name('client.login.post');
Route::post('/logout',   [\App\Http\Controllers\Auth\ClientAuthController::class, 'logout'])->name('client.logout');
Route::middleware('auth:client')->prefix('dashboard')->name('client.')->group(function () {
    Route::get('/', fn() => view('client.dashboard'))->name('dashboard');
});