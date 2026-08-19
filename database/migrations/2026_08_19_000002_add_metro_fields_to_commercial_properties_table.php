<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Доработка по просьбе пользователя: станция метро и минуты пешком до неё —
 * по аналогии с рабочими пространствами (см. 2024_03_01_000001_create_workspaces_table.php)
 * и жилой недвижимостью (см. 2026_08_19_000001_..._residential_properties_table.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_properties', function (Blueprint $table) {
            $table->string('metro_station')->nullable()->after('address');
            $table->unsignedSmallInteger('metro_distance_min')->nullable()->after('metro_station');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_properties', function (Blueprint $table) {
            $table->dropColumn(['metro_station', 'metro_distance_min']);
        });
    }
};
