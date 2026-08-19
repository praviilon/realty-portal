<?php

namespace App\Console\Commands;

use App\Services\StorageCleanupService;
use Illuminate\Console\Command;

/**
 * `php artisan app:cleanup-orphaned-data` — консольная обёртка над
 * App\Services\StorageCleanupService (см. подробное описание там же).
 * Тот же результат доступен через кнопку в админ-панели
 * (App\Filament\Pages\DataCleanup) — эта команда полезна, если нужно
 * запускать очистку по расписанию (cron) без захода в браузер.
 */
class CleanupOrphanedData extends Command
{
    protected $signature = 'app:cleanup-orphaned-data';

    protected $description = 'Удаляет осиротевшие фото объявлений (файлы и записи) и уведомления удалённых пользователей';

    public function handle(StorageCleanupService $service): int
    {
        $result = $service->run();

        $this->info("Осиротевших фото (запись + файл): {$result['orphaned_photos']}");
        $this->info("Файлов без записи в базе: {$result['orphaned_files']}");
        $this->info("Осиротевших уведомлений: {$result['orphaned_notifications']}");
        $this->info("Освобождено места: {$result['freed_human']}");

        return self::SUCCESS;
    }
}
