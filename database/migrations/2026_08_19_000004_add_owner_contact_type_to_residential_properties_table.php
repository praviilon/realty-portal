<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Доработка по просьбе пользователя: на страницах жилой недвижимости тот,
 * кто разместил объявление, всегда отображался как "продавец", даже если
 * он агент или сдаёт объект в аренду. Добавляем owner_type/contact_type —
 * по аналогии с App\Models\Workspace (см. миграцию
 * 2024_03_01_000001_create_workspaces_table). У существующих объявлений
 * таблица уже содержит строки, поэтому у обеих колонок задан default,
 * совпадающий с дефолтами полей мастера создания объявления
 * (App\Livewire\Property\CreateWizard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residential_properties', function (Blueprint $table) {
            $table->enum('owner_type', ['owner', 'agent'])->default('owner')->after('utilities_included');
            $table->enum('contact_type', ['calls_and_messages', 'messages_only'])->default('calls_and_messages')->after('owner_type');
        });
    }

    public function down(): void
    {
        Schema::table('residential_properties', function (Blueprint $table) {
            $table->dropColumn(['owner_type', 'contact_type']);
        });
    }
};
