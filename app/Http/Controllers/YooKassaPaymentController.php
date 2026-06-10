<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\YooKassaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class YooKassaPaymentController extends Controller
{
    public function __construct(
        private YooKassaService $yookassa,
        private CheckoutService $checkout,
    ) {}

    public function webhook(Request $request)
    {
        $event = $request->input('event');
        $object = $request->input('object', []);
        $paymentId = is_array($object) ? ($object['id'] ?? null) : null;

        if (! $paymentId) {
            return response('', 400);
        }

        $payment = $this->yookassa->fetchPayment($paymentId);
        if (! $payment) {
            return response('', 500);
        }

        $orderId = $payment->getMetadata()?->offsetGet('order_id');
        if (! $orderId) {
            Log::warning('YooKassa webhook without order_id in metadata', ['payment_id' => $paymentId]);

            return response('', 200);
        }

        $order = Order::query()->find($orderId);
        if (! $order) {
            return response('', 200);
        }

        Log::info('YooKassa webhook', ['event' => $event, 'order_id' => $order->id, 'payment_id' => $paymentId]);

        $this->yookassa->syncOrderFromPayment($order, $payment);

        return response('', 200);
    }

    public function success(Request $request)
    {
        $order = $this->resolveUserOrder($request);

        if ($order) {
            $this->checkout->syncOrderPayment($order->fresh());
            $order = $order->fresh(['items.product.images', 'orderStatusRef', 'paymentMethodRef', 'paymentStatusRef']);
        }

        return Inertia::render('Payment/Success', [
            'order' => $order,
        ]);
    }

    public function cancel(Request $request)
    {
        $order = $this->resolveUserOrder($request);

        if ($order && $order->isAwaitingPayment() && ! $order->isPaid()) {
            $this->checkout->syncOrderPayment($order);
            $order = $order->fresh();
            if ($order->isAwaitingPayment() && ! $order->isPaid()) {
                $this->checkout->failYooKassaPayment($order, 'canceled');
                $order = $order->fresh();
            }
        }

        return Inertia::render('Payment/Cancel', [
            'order' => $order,
        ]);
    }

    private function resolveUserOrder(Request $request): ?Order
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $orderId = $request->query('order');
        if (! $orderId) {
            return null;
        }

        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product.images', 'orderStatusRef', 'paymentMethodRef', 'paymentStatusRef'])
            ->find($orderId);
    }
}
