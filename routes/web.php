<?php

use App\Livewire\Catalog\CommercialSearch;
use App\Livewire\Catalog\Search as CatalogSearch;
use App\Livewire\Chat\Inbox as ChatInbox;
use App\Livewire\Chat\Thread as ChatThread;
use App\Livewire\CommercialProperty\CreateWizard as CommercialPropertyWizard;
use App\Livewire\CommercialProperty\Show as CommercialPropertyShow;
use App\Livewire\Favorites\Index as FavoritesIndex;
use App\Livewire\Home\Index as HomePage;
use App\Livewire\Property\CreateWizard as ResidentialPropertyWizard;
use App\Livewire\Property\Show as ResidentialPropertyShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');

Route::get('/catalog', CatalogSearch::class)->name('residential.search');

// Эпики 15-16 (Веха 2): каталог коммерческой недвижимости. Регистрируются
// ДО '/catalog/{residentialProperty}' — иначе тот динамический маршрут
// перехватит '/catalog/commercial' и попытается найти жилой объект с id
// "commercial" (404), так и не дойдя до маршрутов ниже.
Route::get('/catalog/commercial', CommercialSearch::class)->name('commercial.search');
Route::get('/catalog/commercial/{commercialProperty}', CommercialPropertyShow::class)->name('commercial.show');

Route::get('/catalog/{residentialProperty}', ResidentialPropertyShow::class)->name('residential.show');

// Эпик 12: правовая информация и помощь (статичные страницы).
Route::view('/about', 'about')->name('about');
Route::view('/help', 'help')->name('help');
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');

Route::middleware(['auth'])->group(function () {
    Route::get('/account/listings/create', ResidentialPropertyWizard::class)->name('residential.create');
    Route::get('/account/listings/{residentialProperty}/edit', ResidentialPropertyWizard::class)->name('residential.edit');

    // Эпик 13 (Веха 2): коммерческая недвижимость — та же схема маршрутов, что и у жилой.
    Route::get('/account/commercial-listings/create', CommercialPropertyWizard::class)->name('commercial.create');
    Route::get('/account/commercial-listings/{commercialProperty}/edit', CommercialPropertyWizard::class)->name('commercial.edit');

    Route::get('/account/chats', ChatInbox::class)->name('chat.index');
    Route::get('/account/chats/{chat}', ChatThread::class)->name('chat.show');

    // Эпик 17 (Веха 2): избранное.
    Route::get('/account/favorites', FavoritesIndex::class)->name('favorites.index');
});

// Без middleware 'verified' — email/телефон не подтверждаются (см. раздел 5 плана).
Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
