<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик 18 (Веха 2): сравнение объявлений. Список сравнения привязан не
 * просто к пользователю, а к паре пользователь+тип объявления (list_type) —
 * так сравнение продажи и аренды, жилья и коммерции не смешиваются в одной
 * таблице. Тип 'workspace' зарезервирован под Веху 3 (см. раздел 3 плана).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('list_type', [
                'residential_sale',
                'residential_rent',
                'commercial_sale',
                'commercial_rent',
                'workspace',
            ]);
            $table->timestamps();

            $table->unique(['user_id', 'list_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_lists');
    }
};
