<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reference_values') || ! Schema::hasTable('products')) {
            return;
        }

        $onOrderId = DB::table('reference_values')
            ->where('type', 'stock_status')
            ->where('code', 'on_order')
            ->value('id');

        if (! $onOrderId) {
            return;
        }

        $inStockId = DB::table('reference_values')
            ->where('type', 'stock_status')
            ->where('code', 'in_stock')
            ->value('id');

        if ($inStockId) {
            DB::table('products')
                ->where('stock_status_id', $onOrderId)
                ->update(['stock_status_id' => $inStockId]);
        }

        DB::table('reference_values')->where('id', $onOrderId)->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('reference_values')) {
            return;
        }

        DB::table('reference_values')->updateOrInsert(
            ['type' => 'stock_status', 'code' => 'on_order'],
            ['label' => 'Под заказ']
        );
    }
};
