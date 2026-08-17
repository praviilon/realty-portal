<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик 18 (Веха 2). Лимит 3 объекта на список и запрет дублей — проверяются
 * в Livewire-компоненте (App\Livewire\Comparison\Button), не на уровне БД,
 * как и указано в техническом плане.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_list_id')->constrained()->cascadeOnDelete();
            $table->string('comparable_type');
            $table->unsignedBigInteger('comparable_id');
            $table->timestamp('added_at');

            $table->index(['comparable_type', 'comparable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_items');
    }
};
