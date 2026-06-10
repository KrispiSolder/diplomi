<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderCancellationService
{
    public function __construct(
        private ProductStockService $stock
    ) {}

    public function cancel(Order $order): void
    {
        if ($order->isCancelled()) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->with('items.product')->find($order->id);
            if (! $order || $order->isCancelled()) {
                return;
            }

            // Офлайн: списание при оформлении. YooKassa: резерв при создании заказа — вернуть, если оплата не прошла.
            $restoreStock = ! $order->isAwaitingPayment()
                || ($order->usesYooKassa() && $order->isAwaitingPayment() && ! $order->isPaid());

            if ($restoreStock) {
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product instanceof Product) {
                        $this->stock->restoreAfterOrderCancel($product, (int) $item->quantity);
                    }
                }
            }

            if ($order->usesYooKassa() && $order->isAwaitingPayment()) {
                $failedId = Order::paymentStatusId('canceled') ?? Order::paymentStatusId('failed');
                if ($failedId) {
                    $order->payment_status_id = $failedId;
                }
            }

            $order->status = 'отменен';
            $order->save();
        });
    }
}
