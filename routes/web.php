<?php

use App\Livewire\Catalog\Search as CatalogSearch;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/catalog', CatalogSearch::class)->name('residential.search');

// TODO (эпик "Детальная карточка объявления", Веха 1, п.6):
// Route::get('/catalog/{residentialProperty}', ResidentialPropertyShow::class)->name('residential.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
