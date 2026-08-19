<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Доработка по просьбе пользователя: у жилой недвижимости не было
 * характеристик "Отделка"/"Отопление"/"Мебель" (уже есть у коммерческой —
 * см. миграцию commercial_properties, те же варианты значений), "особенностей
 * помещения" (аналог floor_features у коммерческой/рабочих пространств —
 * здесь всего одно значение, "нет лифта") и станции метро (аналог
 * metro_station/metro_distance_min у рабочих пространств).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residential_properties', function (Blueprint $table) {
            $table->enum('heating_type', ['central', 'autonomous', 'none'])->nullable()->after('total_floors');
            $table->enum('finishing_type', ['none', 'rough', 'fine'])->nullable()->after('heating_type');
            $table->enum('furniture', ['none', 'partial', 'full'])->nullable()->after('finishing_type');
            $table->json('floor_features')->nullable()->after('furniture');
            $table->string('metro_station')->nullable()->after('address');
            $table->unsignedSmallInteger('metro_distance_min')->nullable()->after('metro_station');
        });
    }

    public function down(): void
    {
        Schema::table('residential_properties', function (Blueprint $table) {
            $table->dropColumn([
                'heating_type',
                'finishing_type',
                'furniture',
                'floor_features',
                'metro_station',
                'metro_distance_min',
            ]);
        });
    }
};
