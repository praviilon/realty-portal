<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик 23 (Веха 3). В отличие от коммерческой недвижимости (1:1 к цене),
 * у рабочего пространства может быть сразу несколько ставок по разным
 * периодам (час/сутки/неделя/месяц) одновременно — поэтому 1:M, а не 1:1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->enum('period', ['hour', 'day', 'week', 'month']);
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->unique(['workspace_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_pricing');
    }
};
