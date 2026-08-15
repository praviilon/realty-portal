<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Раздел 5 технического плана: самостоятельное восстановление пароля и
// подтверждение email осознанно исключены из MVP — пароль сбрасывает
// администратор вручную через Filament, email/телефон не подтверждаются.
// Поэтому здесь нет password.request/password.reset и verification.* маршрутов.

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')
        ->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');
});

Route::middleware('auth')->group(function () {
    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});
