<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockStatus;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    public function deductForOrder(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($product, $quantity) {
            $locked = Product::query()->lockForUpdate()->find($product->id);
            if (! $locked) {
                return;
            }

            if ((int) $locked->quantity <= 0) {
                return;
            }

            $newQty = max(0, (int) $locked->quantity - $quantity);
            $updates = ['quantity' => $newQty];

            if ($newQty <= 0) {
                $outOfStockId = StockStatus::idFor('out_of_stock');
                if ($outOfStockId) {
                    $updates['stock_status_id'] = $outOfStockId;
                }
            }

            Product::whereKey($locked->id)->update($updates);
        });
    }

    public function restoreAfterOrderCancel(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($product, $quantity) {
            $locked = Product::query()->lockForUpdate()->find($product->id);
            if (! $locked) {
                return;
            }

            $newQty = (int) $locked->quantity + $quantity;
            $updates = ['quantity' => $newQty];

            $inStockId = StockStatus::idFor('in_stock');
            if ($inStockId && $newQty > 0) {
                $updates['stock_status_id'] = $inStockId;
            }

            Product::whereKey($locked->id)->update($updates);
        });
    }
}
