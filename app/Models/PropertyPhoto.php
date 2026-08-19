<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class PropertyPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['photoable_type', 'photoable_id', 'path', 'is_main', 'sort_order'];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    /**
     * Доработка по просьбе пользователя: раньше удаление записи о фото
     * (вручную при редактировании объявления, каскадом при удалении
     * объявления/пользователя, либо через очистку осиротевших данных —
     * см. App\Services\StorageCleanupService) НЕ удаляло сам файл в
     * storage/app/public — файлы копились бесконечно. property_photos —
     * полиморфная связь без внешнего ключа на уровне БД (см. миграцию
     * create_property_photos_table), поэтому ON DELETE CASCADE тут в
     * принципе невозможен — единственное надёжное место для удаления
     * файла это модельное событие 'deleting', которое срабатывает при
     * любом ->delete() на экземпляре модели (важно: НЕ срабатывает при
     * массовом PropertyPhoto::where(...)->delete() — везде в проекте
     * поэтому такие места переведены на построчный ->each->delete()).
     */
    protected static function booted(): void
    {
        static::deleting(function (PropertyPhoto $photo) {
            Storage::disk('public')->delete($photo->path);
        });
    }

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }
}
