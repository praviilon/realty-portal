<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('commercial_properties')->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->unsignedInteger('commission')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_sale_details');
    }
};
