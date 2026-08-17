<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик 23 (Веха 3): рабочие пространства — модель + создание объявления.
 * Схема по разделу 3 технического плана ("Веха 3 — рабочие пространства").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('workspace_type', ['workspace', 'office', 'meeting_room', 'conference_room']);
            $table->enum('workspace_subtype', ['fixed', 'flexible'])->nullable();
            $table->enum('building_type', ['administrative', 'business_center', 'residential', 'shopping_center']);
            $table->enum('entrance_type', ['separate', 'common'])->nullable();
            $table->unsignedSmallInteger('floor');
            $table->unsignedSmallInteger('total_floors');
            $table->json('floor_features')->nullable();
            $table->unsignedInteger('area');
            $table->json('access_time')->nullable();
            $table->json('amenities')->nullable();
            $table->json('extra_options')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('metro_station')->nullable();
            $table->unsignedSmallInteger('metro_distance_min')->nullable();
            $table->text('description');
            $table->enum('status', ['moderation', 'active', 'rejected', 'archived'])->default('moderation');
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('deposit')->nullable();
            $table->boolean('utilities_included')->default(false);
            $table->enum('owner_type', ['owner', 'agent']);
            $table->enum('contact_type', ['calls_and_messages', 'messages_only']);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'workspace_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
