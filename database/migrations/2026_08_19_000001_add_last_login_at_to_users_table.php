<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Доработка админ-панели: список пользователей должен показывать дату
 * последнего входа. Существующая колонка sessions.last_activity для этого
 * не подходит (это "последняя активность", обновляется на каждый запрос,
 * а не именно момент входа) — поэтому отдельная колонка на users,
 * заполняется слушателем события Illuminate\Auth\Events\Login
 * (см. App\Providers\AppServiceProvider::boot()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
