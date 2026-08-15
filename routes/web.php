<?php

use App\Livewire\Catalog\Search as CatalogSearch;
use App\Livewire\Chat\Inbox as ChatInbox;
use App\Livewire\Chat\Thread as ChatThread;
use App\Livewire\Home\Index as HomePage;
use App\Livewire\Property\CreateWizard as ResidentialPropertyWizard;
use App\Livewire\Property\Show as ResidentialPropertyShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');

Route::get('/catalog', CatalogSearch::class)->name('residential.search');

Route::get('/catalog/{residentialProperty}', ResidentialPropertyShow::class)->name('residential.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/account/listings/create', ResidentialPropertyWizard::class)->name('residential.create');
    Route::get('/account/listings/{residentialProperty}/edit', ResidentialPropertyWizard::class)->name('residential.edit');

    Route::get('/account/chats', ChatInbox::class)->name('chat.index');
    Route::get('/account/chats/{chat}', ChatThread::class)->name('chat.show');
});

// Без middleware 'verified' — email/телефон не подтверждаются (см. раздел 5 плана).
Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
