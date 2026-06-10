<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $inStockId = $this->stockStatusId('in_stock');
        $outOfStockId = $this->stockStatusId('out_of_stock');

        if (! $inStockId || ! $outOfStockId) {
            return;
        }

        Product::query()
            ->where('quantity', '>', 0)
            ->where('stock_status_id', '!=', $inStockId)
            ->update(['stock_status_id' => $inStockId]);

        Product::query()
            ->where('quantity', '<=', 0)
            ->where('stock_status_id', '!=', $outOfStockId)
            ->update(['stock_status_id' => $outOfStockId]);
    }

    public function down(): void
    {
        // Не восстанавливаем рассинхрон — данные были некорректны.
    }

    private function stockStatusId(string $code): ?int
    {
        if (Schema::hasTable('stock_statuses')) {
            return DB::table('stock_statuses')->where('code', $code)->value('id');
        }

        if (Schema::hasTable('reference_values')) {
            return DB::table('reference_values')
                ->where('type', 'stock_status')
                ->where('code', $code)
                ->value('id');
        }

        return null;
    }
};
