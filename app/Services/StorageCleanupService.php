<?php

namespace App\Services;

use App\Models\CommercialProperty;
use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

/**
 * Подчищает "осиротевшие" данные, которые могли накопиться в базе и в
 * файловом хранилище из-за унаследованного поведения удаления — по
 * просьбе пользователя ("посмотреть как сделать чтобы фото и уведомления
 * которые уже накопились не переполнили память, возможно надо добавить
 * какой-то механизм очистки через админ панель").
 *
 * До доработки (см. App\Models\User::deleteAccount() и
 * App\Models\PropertyPhoto::booted()) удаление объявления/пользователя не
 * удаляло ни файлы фото в storage, ни записи уведомлений — этот сервис
 * подчищает то, что успело накопиться ДО фикса. После фикса подобных
 * "сирот" появляться в норме не должно, но сервис можно запускать и
 * дальше как подстраховку (например, если файл не удалился из-за сбоя
 * диска или БД восстановили из более старого бэкапа, чем файлы).
 *
 * Используется и из консоли (php artisan app:cleanup-orphaned-data), и из
 * админ-панели (App\Filament\Pages\DataCleanup).
 */
class StorageCleanupService
{
    /** @var array<class-string> */
    protected const PHOTOABLE_TYPES = [
        ResidentialProperty::class,
        CommercialProperty::class,
        Workspace::class,
    ];

    /**
     * @return array{orphaned_photos: int, orphaned_files: int, orphaned_notifications: int, freed_bytes: int, freed_human: string}
     */
    public function run(): array
    {
        $freedBytes = 0;

        [$orphanedPhotos, $freedBytes] = $this->cleanupOrphanedPhotoRecords($freedBytes);
        [$orphanedFiles, $freedBytes] = $this->cleanupOrphanedFiles($freedBytes);
        $orphanedNotifications = $this->cleanupOrphanedNotifications();

        return [
            'orphaned_photos' => $orphanedPhotos,
            'orphaned_files' => $orphanedFiles,
            'orphaned_notifications' => $orphanedNotifications,
            'freed_bytes' => $freedBytes,
            'freed_human' => $this->humanBytes($freedBytes),
        ];
    }

    /**
     * Записи PropertyPhoto, у которых объявление (photoable) уже удалено —
     * например, если запись/файл пережили пользователя, удалённого до
     * фикса User::deleteAccount(). Удаляем через модель (не bulk-запросом),
     * чтобы сработало событие 'deleting' и файл в storage тоже удалился.
     *
     * @return array{0: int, 1: int}
     */
    protected function cleanupOrphanedPhotoRecords(int $freedBytes): array
    {
        $count = 0;

        foreach (self::PHOTOABLE_TYPES as $type) {
            $existingIds = $type::query()->pluck('id');

            $orphaned = PropertyPhoto::query()
                ->where('photoable_type', $type)
                ->whereNotIn('photoable_id', $existingIds)
                ->get();

            foreach ($orphaned as $photo) {
                $freedBytes += $this->fileSize($photo->path);
                $photo->delete();
                $count++;
            }
        }

        return [$count, $freedBytes];
    }

    /**
     * Файлы в storage/app/public/property-photos и .../avatars, на которые
     * не ссылается ни одна запись в БД — например, временные файлы,
     * "пережившие" сбой при сохранении объявления, или файлы, оставшиеся
     * от той же проблемы, что и осиротевшие записи выше.
     *
     * @return array{0: int, 1: int}
     */
    protected function cleanupOrphanedFiles(int $freedBytes): array
    {
        $disk = Storage::disk('public');

        $knownPaths = array_flip(
            PropertyPhoto::query()->pluck('path')
                ->merge(User::query()->whereNotNull('avatar_path')->pluck('avatar_path'))
                ->all()
        );

        $count = 0;

        foreach (['property-photos', 'avatars'] as $directory) {
            foreach ($disk->allFiles($directory) as $file) {
                if (! isset($knownPaths[$file])) {
                    $freedBytes += $disk->size($file);
                    $disk->delete($file);
                    $count++;
                }
            }
        }

        return [$count, $freedBytes];
    }

    /**
     * Уведомления, у которых пользователь (notifiable) уже удалён —
     * например, оставшиеся от удаления пользователя до фикса
     * User::deleteAccount(). У уведомлений нет файлов в storage, поэтому
     * обычный bulk delete() безопасен и не нуждается в событиях модели.
     */
    protected function cleanupOrphanedNotifications(): int
    {
        $existingUserIds = User::query()->pluck('id');

        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->whereNotIn('notifiable_id', $existingUserIds)
            ->delete();
    }

    protected function fileSize(string $path): int
    {
        $disk = Storage::disk('public');

        return $disk->exists($path) ? $disk->size($path) : 0;
    }

    protected function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' Б';
        }

        $value = $bytes;
        foreach (['КБ', 'МБ', 'ГБ'] as $unit) {
            $value /= 1024;
            if ($value < 1024) {
                return round($value, 1) . ' ' . $unit;
            }
        }

        return round($value, 1) . ' ГБ';
    }
}
