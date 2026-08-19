<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Аналогичная доработка для коммерческой недвижимости — см. миграцию
 * 2026_08_19_000004_add_owner_contact_type_to_residential_properties_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_properties', function (Blueprint $table) {
            $table->enum('owner_type', ['owner', 'agent'])->default('owner')->after('furniture');
            $table->enum('contact_type', ['calls_and_messages', 'messages_only'])->default('calls_and_messages')->after('owner_type');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_properties', function (Blueprint $table) {
            $table->dropColumn(['owner_type', 'contact_type']);
        });
    }
};
