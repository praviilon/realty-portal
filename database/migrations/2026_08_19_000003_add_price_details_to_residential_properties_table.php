<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Доработка по просьбе пользователя: шаг 4 мастера создания объявления
 * ("Цена") для жилой недвижимости по аналогии с коммерческой —
 * для аренды нужны депозит/комиссия/тип аренды/"коммунальные платежи
 * включены", для продажи — комиссия.
 *
 * В отличие от коммерческой недвижимости (где цена вынесена в отдельные
 * таблицы commercial_rent_details/commercial_sale_details 1:1 — см.
 * 2024_02_01_000002/000003), у жилой недвижимости уже была единая колонка
 * price, которая одинаково используется и для цены продажи, и для цены
 * аренды в месяц, и на неё уже завязаны каталог (фильтр по цене),
 * сравнение, избранное, кабинет и главная страница. Отдельная таблица
 * потребовала бы менять всю эту цепочку без реальной необходимости — здесь
 * достаточно ДОБАВИТЬ недостающие поля рядом с уже существующей price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residential_properties', function (Blueprint $table) {
            $table->unsignedInteger('deposit')->nullable()->after('price');
            $table->unsignedInteger('commission')->nullable()->after('deposit');
            $table->enum('rent_type', ['direct', 'sublease'])->nullable()->after('commission');
            $table->boolean('utilities_included')->default(false)->after('rent_type');
        });
    }

    public function down(): void
    {
        Schema::table('residential_properties', function (Blueprint $table) {
            $table->dropColumn(['deposit', 'commission', 'rent_type', 'utilities_included']);
        });
    }
};
