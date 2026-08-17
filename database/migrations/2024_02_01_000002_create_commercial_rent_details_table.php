<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_rent_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('commercial_properties')->cascadeOnDelete();
            $table->unsignedInteger('price_per_month');
            $table->unsignedInteger('deposit')->nullable();
            $table->unsignedInteger('commission')->nullable();
            $table->boolean('utilities_included')->default(false);
            $table->enum('rent_type', ['direct', 'sublease'])->default('direct');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_rent_details');
    }
};
