<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residential_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('deal_type', ['sale', 'rent']);
            $table->enum('property_type', ['apartment', 'house', 'room', 'studio']);
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('area');
            $table->unsignedSmallInteger('floor');
            $table->unsignedSmallInteger('total_floors');
            $table->unsignedInteger('price');
            $table->text('description');
            $table->enum('status', ['moderation', 'active', 'rejected', 'archived'])->default('moderation');
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'deal_type']);
            // Примечание: для полигонного поиска по карте (эпик "Карта — выделение области", Веха 2)
            // понадобится отдельная миграция с настоящим geometry-полем POINT + SPATIAL INDEX —
            // decimal-колонки lat/lng для обычной фильтрации и сортировки достаточно уже сейчас.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residential_properties');
    }
};
