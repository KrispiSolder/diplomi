<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'yandex_id')) {
                $table->string('yandex_id')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'yandex_email')) {
                $table->string('yandex_email')->nullable()->after('yandex_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'yandex_email')) {
                $table->dropColumn('yandex_email');
            }
            if (Schema::hasColumn('users', 'yandex_id')) {
                $table->dropColumn('yandex_id');
            }
        });
    }
};

