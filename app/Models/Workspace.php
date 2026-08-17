<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_type',
        'workspace_subtype',
        'building_type',
        'entrance_type',
        'floor',
        'total_floors',
        'floor_features',
        'area',
        'access_time',
        'amenities',
        'extra_options',
        'address',
        'lat',
        'lng',
        'metro_station',
        'metro_distance_min',
        'description',
        'status',
        'rejection_reason',
        'deposit',
        'utilities_included',
        'owner_type',
        'contact_type',
        'views_count',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'floor_features' => 'array',
        'access_time' => 'array',
        'amenities' => 'array',
        'extra_options' => 'array',
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

    public function pricing(): HasMany
    {
        return $this->hasMany(WorkspacePricing::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Тип списка сравнения (эпик 28, Веха 3) — единый для всех рабочих
     * пространств (в отличие от жилья/коммерции цена не делится на
     * продажу/аренду, поэтому один тип на всю модель).
     */
    public function comparisonListType(): string
    {
        return 'workspace';
    }

    /**
     * Самая дешёвая ставка — используется на карточках каталога и пинах карты,
     * чтобы не показывать сразу все периоды там, где не хватает места.
     */
    public function getDisplayPriceAttribute(): ?int
    {
        return $this->cheapestPricing?->price;
    }

    public function getCheapestPricingAttribute(): ?WorkspacePricing
    {
        return $this->pricing->sortBy('price')->first();
    }

    public static function workspaceTypeLabels(): array
    {
        return [
            'workspace' => 'Рабочее место',
            'office' => 'Офис',
            'meeting_room' => 'Переговорная',
            'conference_room' => 'Конференц-зал',
        ];
    }

    public static function workspaceSubtypeLabels(): array
    {
        return [
            'fixed' => 'Закреплённое место',
            'flexible' => 'Свободное место (hot desk)',
        ];
    }

    public static function buildingTypeLabels(): array
    {
        return CommercialProperty::buildingTypeLabels();
    }

    public static function entranceTypeLabels(): array
    {
        return CommercialProperty::entranceTypeLabels();
    }

    public static function floorFeatureLabels(): array
    {
        return [
            'separate_entrance' => 'Отдельный вход с улицы',
            'parking' => 'Парковка',
            'security' => 'Охрана/видеонаблюдение',
            'reception' => 'Ресепшн',
        ];
    }

    public static function amenityLabels(): array
    {
        return [
            'wifi' => 'Wi-Fi',
            'coffee' => 'Кофе/чай',
            'kitchen' => 'Кухня',
            'printer' => 'Принтер/сканер',
            'whiteboard' => 'Доска для записей',
            'tv_screen' => 'Экран/проектор',
            'phone_booth' => 'Телефонная будка',
            'air_conditioning' => 'Кондиционер',
        ];
    }

    public static function extraOptionLabels(): array
    {
        return [
            'cleaning' => 'Уборка',
            'catering' => 'Кейтеринг',
            'reception_service' => 'Услуги ресепшн',
            'secretary_support' => 'Секретарская поддержка',
            'tech_support' => 'Техническая поддержка',
        ];
    }

    public static function accessTimeTypeLabels(): array
    {
        return [
            'weekdays' => 'Пн–Пт',
            'weekends' => 'Сб–Вс',
            'daily' => 'Ежедневно',
            'round_the_clock' => 'Круглосуточно',
        ];
    }

    public static function ownerTypeLabels(): array
    {
        return [
            'owner' => 'Собственник',
            'agent' => 'Агент',
        ];
    }

    public static function contactTypeLabels(): array
    {
        return [
            'calls_and_messages' => 'Звонки и сообщения',
            'messages_only' => 'Только сообщения',
        ];
    }

    public static function pricingPeriodLabels(): array
    {
        return [
            'hour' => 'час',
            'day' => 'сутки',
            'week' => 'неделя',
            'month' => 'месяц',
        ];
    }

    public function getWorkspaceTypeLabelAttribute(): string
    {
        return self::workspaceTypeLabels()[$this->workspace_type] ?? $this->workspace_type;
    }

    public function getBuildingTypeLabelAttribute(): string
    {
        return self::buildingTypeLabels()[$this->building_type] ?? $this->building_type;
    }

    public function getOwnerTypeLabelAttribute(): string
    {
        return self::ownerTypeLabels()[$this->owner_type] ?? $this->owner_type;
    }

    public function getContactTypeLabelAttribute(): string
    {
        return self::contactTypeLabels()[$this->contact_type] ?? $this->contact_type;
    }
}
