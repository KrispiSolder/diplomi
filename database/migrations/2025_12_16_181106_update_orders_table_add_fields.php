<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Добавляем total_amount если его нет
            if (!Schema::hasColumn('orders', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('total_price');
            }
            
            // Добавляем delivery_address если его нет
            if (!Schema::hasColumn('orders', 'delivery_address')) {
                $table->string('delivery_address')->nullable()->after('status');
            }
            
            // Делаем total_price nullable если он не nullable
            if (Schema::hasColumn('orders', 'total_price')) {
                $table->decimal('total_price', 10, 2)->nullable()->change();
            }
            
            // Изменяем статус на более гибкий
            if (Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('в обработке')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('orders', 'delivery_address')) {
                $table->dropColumn('delivery_address');
            }
        });
    }
};
