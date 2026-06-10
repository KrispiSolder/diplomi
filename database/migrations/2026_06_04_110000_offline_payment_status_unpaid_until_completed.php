<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OFFLINE_METHODS = ['cash', 'card_courier'];

    public function up(): void
    {
        DB::table('reference_values')->updateOrInsert(
            ['type' => 'payment_status', 'code' => 'unpaid'],
            [
                'label' => 'Не оплачен',
                'description' => 'Оплата при получении ещё не принята',
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        $unpaidId = DB::table('reference_values')
            ->where('type', 'payment_status')
            ->where('code', 'unpaid')
            ->value('id');

        $paidId = DB::table('reference_values')
            ->where('type', 'payment_status')
            ->where('code', 'paid')
            ->value('id');

        $completedId = DB::table('reference_values')
            ->where('type', 'order_status')
            ->where(function ($q) {
                $q->where('label', 'выполнен')->orWhere('code', 'completed');
            })
            ->value('id');

        if (! $unpaidId || ! Schema::hasTable('orders')) {
            return;
        }

        $offlineMethodIds = DB::table('reference_values')
            ->where('type', 'payment_method')
            ->whereIn('code', self::OFFLINE_METHODS)
            ->pluck('id')
            ->all();

        if ($offlineMethodIds === []) {
            return;
        }

        DB::table('orders')
            ->whereIn('payment_method_id', $offlineMethodIds)
            ->update(['payment_status_id' => $unpaidId]);

        if ($completedId && $paidId) {
            DB::table('orders')
                ->whereIn('payment_method_id', $offlineMethodIds)
                ->where('order_status_id', $completedId)
                ->update(['payment_status_id' => $paidId]);
        }
    }

    public function down(): void
    {
        DB::table('reference_values')
            ->where('type', 'payment_status')
            ->where('code', 'unpaid')
            ->delete();
    }
};
