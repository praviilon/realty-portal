<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ResidentialProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'deal_type',
        'property_type',
        'address',
        'lat',
        'lng',
        'area',
        'floor',
        'total_floors',
        'price',
        'description',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(PropertyPhoto::class, 'photoable');
    }

    /**
     * Главное фото для мини-карточек (каталог, главная) — доработка после
     * Вехи 3: отдельная связь с MorphOne, чтобы не тянуть все фото ради
     * одной миниатюры (сортировка: is_main первым, затем sort_order).
     */
    public function mainPhoto(): MorphOne
    {
        return $this->morphOne(PropertyPhoto::class, 'photoable')
            ->orderByDesc('is_main')
            ->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public static function dealTypeLabels(): array
    {
        return [
            'sale' => 'Продажа',
            'rent' => 'Аренда',
        ];
    }

    public static function propertyTypeLabels(): array
    {
        return [
            'apartment' => 'Квартира',
            'house' => 'Дом',
            'room' => 'Комната',
            'studio' => 'Студия',
        ];
    }

    public function getDealTypeLabelAttribute(): string
    {
        return self::dealTypeLabels()[$this->deal_type] ?? $this->deal_type;
    }

    public function getPropertyTypeLabelAttribute(): string
    {
        return self::propertyTypeLabels()[$this->property_type] ?? $this->property_type;
    }

    /**
     * Тип списка сравнения (эпик 18, Веха 2) — см. аналогичный метод
     * в App\Models\CommercialProperty.
     */
    public function comparisonListType(): string
    {
        return 'residential_' . $this->deal_type;
    }
}
