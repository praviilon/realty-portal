<?php

use App\Livewire\Catalog\Search as CatalogSearch;
use App\Livewire\Home\Index as HomePage;
use App\Livewire\Property\Show as ResidentialPropertyShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');

Route::get('/catalog', CatalogSearch::class)->name('residential.search');

// Базовая версия — полноценная карточка (фото, карта, контакт продавца) — эпик 6.
Route::get('/catalog/{residentialProperty}', ResidentialPropertyShow::class)->name('residential.show');

// Без middleware 'verified' — email/телефон не подтверждаются (см. раздел 5 плана).
Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
