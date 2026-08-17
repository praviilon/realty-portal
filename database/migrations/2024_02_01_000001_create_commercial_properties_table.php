<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('deal_type', ['sale', 'rent']);
            $table->enum('purpose_type', ['office', 'retail', 'warehouse', 'free']);
            $table->enum('building_type', ['administrative', 'business_center', 'residential', 'shopping_center']);
            $table->enum('entrance_type', ['separate', 'common'])->nullable();
            $table->unsignedSmallInteger('floor');
            $table->json('floor_features')->nullable();
            $table->unsignedSmallInteger('total_floors');
            $table->unsignedInteger('area');
            $table->decimal('ceiling_height', 4, 2)->nullable();
            $table->enum('heating_type', ['central', 'autonomous', 'none'])->nullable();
            $table->enum('finishing_type', ['none', 'rough', 'fine'])->nullable();
            $table->enum('furniture', ['none', 'partial', 'full'])->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->text('description');
            $table->enum('status', ['moderation', 'active', 'rejected', 'archived'])->default('moderation');
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'deal_type']);
            $table->index(['status', 'purpose_type']);
            // Как и у residential_properties: настоящее geometry-поле POINT + SPATIAL INDEX
            // для полигонного поиска добавляется отдельной миграцией в эпике
            // «Карта — выделение области» (Веха 2) — см. примечание там же.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_properties');
    }
};
