<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Унификация каталогов (доработка по просьбе пользователя — "выбор области
 * на карте" должен работать одинаково во всех трёх каталогах): та же
 * generated geometry-колонка + SPATIAL INDEX, что и в
 * 2024_02_04_000001_add_location_point_to_residential_properties_table —
 * см. подробный комментарий там про причины (плоский SRID 0, не 4326).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE workspaces
            ADD COLUMN location POINT GENERATED ALWAYS AS (POINT(lng, lat)) STORED NOT NULL,
            ADD SPATIAL INDEX workspaces_location_spatial (location)
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE workspaces DROP INDEX workspaces_location_spatial, DROP COLUMN location');
    }
};
