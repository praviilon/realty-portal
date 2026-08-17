<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Эпик 19 (Веха 2): выделение области на карте. Колонка location —
 * сгенерированное geometry-поле POINT, которое всегда синхронизировано
 * с lat/lng (MySQL пересчитывает значение сам при каждой записи, отдельно
 * поддерживать её в модели/наблюдателе не нужно). SPATIAL INDEX ускоряет
 * фильтрацию через ST_Contains(полигон, location) в App\Livewire\Catalog\Search.
 *
 * Сознательно без привязки к SRID 4326: для географического SRID MySQL
 * считает ST_Contains по эллипсоиду и результат зависит от направления
 * обхода вершин полигона (по часовой/против часовой стрелки) — на практике
 * пользователь произвольно обводит область на карте, гарантировать нужное
 * направление на фронтенде неудобно и хрупко. Планарный (SRID 0, "плоская
 * земля") расчёт даёт предсказуемый результат и более чем достаточную
 * точность для выделения области в масштабе города/района.
 */
return new class extends Migration
{
    /**
     * Генерированная geometry-колонка и SPATIAL INDEX — синтаксис, специфичный
     * для MySQL. В тестовом окружении (sqlite, см. phpunit.xml) миграция
     * ничего не делает — там App\Livewire\Catalog\Search сам считает
     * попадание точки в полигон на PHP (тот же результат, без ST_Contains).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE residential_properties
            ADD COLUMN location POINT GENERATED ALWAYS AS (POINT(lng, lat)) STORED NOT NULL,
            ADD SPATIAL INDEX residential_properties_location_spatial (location)
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE residential_properties DROP INDEX residential_properties_location_spatial, DROP COLUMN location');
    }
};
