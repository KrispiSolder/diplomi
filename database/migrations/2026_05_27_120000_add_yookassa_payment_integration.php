<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['type' => 'payment_status', 'code' => 'pending', 'label' => 'Ожидает оплаты'],
            ['type' => 'payment_status', 'code' => 'paid', 'label' => 'Оплачен'],
            ['type' => 'payment_status', 'code' => 'failed', 'label' => 'Ошибка оплаты'],
            ['type' => 'payment_status', 'code' => 'canceled', 'label' => 'Оплата отменена'],
        ] as $row) {
            DB::table('reference_values')->updateOrInsert(
                ['type' => $row['type'], 'code' => $row['code']],
                ['label' => $row['label']]
            );
        }

        DB::table('reference_values')->updateOrInsert(
            ['type' => 'payment_method', 'code' => 'yookassa'],
            ['label' => 'ЮKassa (онлайн)']
        );

        DB::table('reference_values')->updateOrInsert(
            ['type' => 'order_status', 'code' => 'awaiting_payment'],
            ['label' => 'ожидает оплаты']
        );

        if (Schema::hasTable('orders')) {
            if (! Schema::hasColumn('orders', 'payment_status_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreignId('payment_status_id')
                        ->nullable()
                        ->after('payment_method_id')
                        ->constrained('reference_values')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('orders', 'yookassa_payment_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('yookassa_payment_id', 64)->nullable()->after('payment_status_id');
                });
            }

            $paidId = DB::table('reference_values')
                ->where('type', 'payment_status')
                ->where('code', 'paid')
                ->value('id');

            if ($paidId) {
                DB::table('orders')->whereNull('payment_status_id')->update(['payment_status_id' => $paidId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'yookassa_payment_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropColumn('yookassa_payment_id');
                });
            }

            if (Schema::hasColumn('orders', 'payment_status_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('payment_status_id');
                });
            }
        }

        DB::table('reference_values')->where('type', 'payment_method')->where('code', 'yookassa')->delete();
        DB::table('reference_values')->where('type', 'order_status')->where('code', 'awaiting_payment')->delete();
        DB::table('reference_values')->where('type', 'payment_status')->delete();
    }
};
