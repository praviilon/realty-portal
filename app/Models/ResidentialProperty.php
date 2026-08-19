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
        'metro_station',
        'metro_distance_min',
        'area',
        'floor',
        'total_floors',
        'heating_type',
        'finishing_type',
        'furniture',
        'floor_features',
        'price',
        'deposit',
        'commission',
        'rent_type',
        'utilities_included',
        'owner_type',
        'contact_type',
        'description',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'floor_features' => 'array',
        'utilities_included' => 'boolean',
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
     * Отопление/отделка/мебель — те же варианты значений, что и у
     * коммерческой недвижимости (по просьбе пользователя), поэтому метки
     * просто делегируются в App\Models\CommercialProperty, а не дублируются
     * здесь — по тому же принципу, что и App\Models\Workspace::buildingTypeLabels()/
     * entranceTypeLabels().
     */
    public static function heatingTypeLabels(): array
    {
        return CommercialProperty::heatingTypeLabels();
    }

    public static function finishingTypeLabels(): array
    {
        return CommercialProperty::finishingTypeLabels();
    }

    public static function furnitureLabels(): array
    {
        return CommercialProperty::furnitureLabels();
    }

    public static function rentTypeLabels(): array
    {
        return CommercialProperty::rentTypeLabels();
    }

    /**
     * "Особенности помещения" — по аналогии с коммерческой недвижимостью и
     * рабочими пространствами, но для жилой недвижимости пока только один
     * пункт (по просьбе пользователя).
     */
    public static function floorFeatureLabels(): array
    {
        return [
            'no_elevator' => 'Нет лифта',
        ];
    }

    /**
     * Кто разместил объявление (собственник/агент) и способ связи —
     * доработка по просьбе пользователя, чтобы на странице объекта не
     * всегда отображалось "Продавец" (см. show.blade.php). Значения те же,
     * что и у рабочих пространств, поэтому метки делегируются в
     * App\Models\Workspace, а не дублируются (см. аналогичный принцип для
     * heatingTypeLabels()/finishingTypeLabels() выше).
     */
    public static function ownerTypeLabels(): array
    {
        return Workspace::ownerTypeLabels();
    }

    public static function contactTypeLabels(): array
    {
        return Workspace::contactTypeLabels();
    }

    public function getOwnerTypeLabelAttribute(): string
    {
        return self::ownerTypeLabels()[$this->owner_type] ?? $this->owner_type;
    }

    public function getContactTypeLabelAttribute(): string
    {
        return self::contactTypeLabels()[$this->contact_type] ?? $this->contact_type;
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
