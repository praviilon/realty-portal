<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('listable'); // listable_id + listable_type
            $table->timestamps();

            $table->unique(['buyer_id', 'seller_id', 'listable_type', 'listable_id'], 'chats_unique_thread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
