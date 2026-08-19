<?php

namespace App\Filament\Pages;

use App\Services\StorageCleanupService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Админ-страница для ручной очистки "осиротевших" данных — по просьбе
 * пользователя ("возможно надо добавить какой-то механизм очистки через
 * админ панель"). Логика — в App\Services\StorageCleanupService (там же
 * подробно объяснено, откуда вообще берутся "осиротевшие" фото/уведомления).
 */
class DataCleanup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationLabel = 'Очистка данных';

    protected static ?string $title = 'Очистка данных';

    protected static string $view = 'filament.pages.data-cleanup';

    /** @var array{orphaned_photos: int, orphaned_files: int, orphaned_notifications: int, freed_bytes: int, freed_human: string}|null */
    public ?array $lastResult = null;

    public function runCleanup(): void
    {
        $this->lastResult = app(StorageCleanupService::class)->run();

        Notification::make()
            ->title('Очистка завершена')
            ->body(
                "Осиротевших фото: {$this->lastResult['orphaned_photos']}. " .
                "Файлов без записи в базе: {$this->lastResult['orphaned_files']}. " .
                "Осиротевших уведомлений: {$this->lastResult['orphaned_notifications']}. " .
                "Освобождено: {$this->lastResult['freed_human']}."
            )
            ->success()
            ->send();
    }
}
