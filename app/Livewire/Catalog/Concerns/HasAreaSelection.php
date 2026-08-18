<?php

namespace App\Livewire\Catalog\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Выделение области на карте (эпик 19, Веха 2) — общая логика для всех трёх
 * каталогов. Изначально реализовано только в App\Livewire\Catalog\Search
 * (жилая недвижимость); здесь вынесено в трейт по просьбе пользователя
 * унифицировать функционал во всех каталогах (коммерция, рабочие
 * пространства), чтобы не дублировать идентичный код с нуля в каждом
 * компоненте. Search.php сознательно оставлен как есть (уже проверенный,
 * боевой код) — трейт этот метод там не заменяет, а используется только в
 * CommercialSearch и WorkspaceSearch при добавлении той же функции им.
 *
 * Использующий класс должен:
 *  - в filteredQuery() добавить `if (count($this->areaPolygon) >= 3) { $query
 *    = $this->applyAreaFilter($query); }`;
 *  - иметь миграцию с generated-колонкой `location` (POINT) + SPATIAL INDEX
 *    на своей таблице для MySQL (см.
 *    2026_08_18_000001/000002_add_location_point_to_*_table) — на любой
 *    другой СУБД (sqlite в тестах) используется ray casting на PHP, колонка
 *    location не нужна.
 */
trait HasAreaSelection
{
    /**
     * Массив вершин полигона [['lat' => ..., 'lng' => ...], ...], минимум 3.
     */
    #[Url]
    public array $areaPolygon = [];

    /**
     * Вызывается из resources/js/yandex-map.js после того, как пользователь
     * выделил на карте прямоугольную область (клик по двум противоположным углам).
     */
    public function applyAreaSelection(array $points): void
    {
        $this->areaPolygon = $points;
        $this->resetPage();
    }

    public function clearAreaSelection(): void
    {
        $this->areaPolygon = [];
        $this->resetPage();
    }

    protected function applyAreaFilter(Builder $query, string $locationColumn = 'location'): Builder
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $ring = collect($this->areaPolygon)
                ->push($this->areaPolygon[0])
                ->map(fn (array $point) => $point['lng'].' '.$point['lat'])
                ->implode(', ');

            return $query->whereRaw("ST_Contains(ST_GeomFromText(?), {$locationColumn})", ["POLYGON(({$ring}))"]);
        }

        $polygon = $this->areaPolygon;

        $matchingIds = (clone $query)
            ->get(['id', 'lat', 'lng'])
            ->filter(fn ($listing) => static::areaSelectionPointInPolygon(
                (float) $listing->lat,
                (float) $listing->lng,
                $polygon
            ))
            ->pluck('id');

        return $query->whereIn('id', $matchingIds);
    }

    /**
     * Простой ray casting — является ли точка (lat, lng) внутри полигона,
     * заданного массивом вершин [['lat' => ..., 'lng' => ...], ...].
     */
    protected static function areaSelectionPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $latI = (float) $polygon[$i]['lat'];
            $lngI = (float) $polygon[$i]['lng'];
            $latJ = (float) $polygon[$j]['lat'];
            $lngJ = (float) $polygon[$j]['lng'];

            $intersects = (($latI > $lat) !== ($latJ > $lat))
                && ($lng < ($lngJ - $lngI) * ($lat - $latI) / ($latJ - $latI) + $lngI);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
