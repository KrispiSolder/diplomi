<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'payment_status_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['payment_status_id']);
                });
            } catch (\Throwable) {
                // Внешний ключ мог не создаваться или иметь другое имя
            }

            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('payment_status_id');
            });
        }

        if (Schema::hasColumn('orders', 'yandex_pay_order_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('yandex_pay_order_id');
            });
        }

        DB::table('reference_values')->where('type', 'payment_status')->delete();

        foreach ([
            ['code' => 'cash', 'label' => 'Наличные'],
            ['code' => 'card_courier', 'label' => 'Картой курьеру'],
        ] as $row) {
            DB::table('reference_values')->updateOrInsert(
                ['type' => 'payment_method', 'code' => $row['code']],
                ['label' => $row['label']]
            );
        }

        if (! Schema::hasColumn('orders', 'payment_method_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('payment_method_id')
                    ->nullable()
                    ->after('order_status_id')
                    ->constrained('reference_values')
                    ->nullOnDelete();
            });
        }

        $cashId = DB::table('reference_values')
            ->where('type', 'payment_method')
            ->where('code', 'cash')
            ->value('id');

        if ($cashId) {
            DB::table('orders')->whereNull('payment_method_id')->update(['payment_method_id' => $cashId]);
        }
    }

    public function down(): void
    {
        // Обратная миграция не восстанавливает Yandex Pay.
    }
};
