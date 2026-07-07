<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CaddyController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Caddy on_demand_tls "ask" endpoint (called server-to-server, no session).
Route::get('/caddy/allowed', [CaddyController::class, 'allowed'])->name('caddy.allowed');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [WebsiteController::class, 'index'])->name('dashboard');

    Route::get('/websites/create', [WebsiteController::class, 'create'])->name('websites.create');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
    Route::get('/websites/{website}', [WebsiteController::class, 'show'])->name('websites.show');
    Route::get('/websites/{website}/status', [WebsiteController::class, 'status'])->name('websites.status');
    Route::post('/websites/{website}/regenerate', [WebsiteController::class, 'regenerate'])->name('websites.regenerate');
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy'])->name('websites.destroy');

    Route::get('/websites/{website}/preview/{path?}', PreviewController::class)
        ->where('path', '.*')
        ->name('websites.preview');

    Route::post('/websites/{website}/publish', [PublishController::class, 'store'])->name('websites.publish');
    Route::delete('/websites/{website}/publish', [PublishController::class, 'destroy'])->name('websites.unpublish');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/purchase', [BillingController::class, 'purchase'])->name('billing.purchase');
});
