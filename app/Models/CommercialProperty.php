<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CommercialProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'deal_type',
        'purpose_type',
        'building_type',
        'entrance_type',
        'floor',
        'floor_features',
        'total_floors',
        'area',
        'ceiling_height',
        'heating_type',
        'finishing_type',
        'furniture',
        'address',
        'lat',
        'lng',
        'description',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'ceiling_height' => 'decimal:2',
        'floor_features' => 'array',
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

    public function rentDetail(): HasOne
    {
        return $this->hasOne(CommercialRentDetail::class, 'property_id');
    }

    public function saleDetail(): HasOne
    {
        return $this->hasOne(CommercialSaleDetail::class, 'property_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Единая цена для отображения в карточках/каталоге независимо от deal_type —
     * чтобы не дублировать ветвление "аренда/продажа" по всем Blade-шаблонам.
     */
    public function getDisplayPriceAttribute(): ?int
    {
        return $this->deal_type === 'rent'
            ? $this->rentDetail?->price_per_month
            : $this->saleDetail?->price;
    }

    public static function dealTypeLabels(): array
    {
        return [
            'sale' => 'Продажа',
            'rent' => 'Аренда',
        ];
    }

    public static function purposeTypeLabels(): array
    {
        return [
            'office' => 'Офис',
            'retail' => 'Торговое помещение',
            'warehouse' => 'Склад',
            'free' => 'Свободное назначение',
        ];
    }

    public static function buildingTypeLabels(): array
    {
        return [
            'administrative' => 'Административное здание',
            'business_center' => 'Бизнес-центр',
            'residential' => 'Жилой дом',
            'shopping_center' => 'Торговый центр',
        ];
    }

    public static function entranceTypeLabels(): array
    {
        return [
            'separate' => 'Отдельный вход',
            'common' => 'Общий вход',
        ];
    }

    public static function heatingTypeLabels(): array
    {
        return [
            'central' => 'Центральное',
            'autonomous' => 'Автономное',
            'none' => 'Отсутствует',
        ];
    }

    public static function finishingTypeLabels(): array
    {
        return [
            'none' => 'Без отделки',
            'rough' => 'Черновая',
            'fine' => 'Чистовая',
        ];
    }

    public static function furnitureLabels(): array
    {
        return [
            'none' => 'Без мебели',
            'partial' => 'Частично меблировано',
            'full' => 'Полностью меблировано',
        ];
    }

    public static function floorFeatureLabels(): array
    {
        return [
            'separate_entrance' => 'Отдельный вход с улицы',
            'shop_window' => 'Витринные окна',
            'high_traffic' => 'Проходное место',
            'parking' => 'Парковка',
            'security' => 'Охрана/видеонаблюдение',
        ];
    }

    public static function rentTypeLabels(): array
    {
        return [
            'direct' => 'Прямая аренда',
            'sublease' => 'Субаренда',
        ];
    }

    public function getDealTypeLabelAttribute(): string
    {
        return self::dealTypeLabels()[$this->deal_type] ?? $this->deal_type;
    }

    public function getPurposeTypeLabelAttribute(): string
    {
        return self::purposeTypeLabels()[$this->purpose_type] ?? $this->purpose_type;
    }

    public function getBuildingTypeLabelAttribute(): string
    {
        return self::buildingTypeLabels()[$this->building_type] ?? $this->building_type;
    }

    /**
     * Тип списка сравнения (эпик 18, Веха 2) — по нему пользователь может
     * одновременно сравнивать до 3 объектов только внутри одного и того же
     * сочетания «тип объекта + вид сделки», см. App\Livewire\Comparison\Button.
     */
    public function comparisonListType(): string
    {
        return 'commercial_' . $this->deal_type;
    }
}
