<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private OrderCancellationService $cancellation
    ) {
        $this->middleware('auth');
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->isCancelled()) {
            return back()->withErrors(['order' => 'Заказ уже отменён']);
        }

        if (! $order->canBeCancelledByUser()) {
            $message = $order->usesYooKassa() && $order->status === 'в обработке'
                ? 'Заказ с онлайн-оплатой картой отменить нельзя'
                : 'Отмена недоступна для этого заказа';

            return back()->withErrors(['order' => $message]);
        }

        $wasAwaitingPayment = $order->isAwaitingPayment();

        $this->cancellation->cancel($order);

        $message = $wasAwaitingPayment
            ? 'Заказ отменён'
            : 'Заказ отменён, товары возвращены на склад';

        return back()->with('success', $message);
    }
}
