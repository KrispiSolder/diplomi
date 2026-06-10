<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'user@example.com')->first();
        $products = Product::query()->take(3)->get();

        if (! $user || $products->count() < 3) {
            $this->command?->warn('Недостаточно данных для demo-заказов.');

            return;
        }

        $statuses = [
            OrderStatus::idForLabel('в обработке'),
            OrderStatus::idForLabel('отправлен'),
            OrderStatus::idForLabel('выполнен'),
        ];

        $paymentMethodId = PaymentMethod::idFor('cash');
        $paidStatusId = PaymentStatus::idFor('paid');
        $unpaidStatusId = PaymentStatus::idFor('unpaid')
            ?? PaymentStatus::idFor('pending');

        $dates = [now(), now()->subDays(2), now()->subDays(5)];

        for ($i = 0; $i < 3; $i++) {
            $completedIndex = 2;
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_status_id' => $statuses[$i],
                'payment_method_id' => $paymentMethodId,
                'payment_status_id' => $i === $completedIndex ? $paidStatusId : $unpaidStatusId,
                'delivery_address' => 'Тестовый адрес, д. '.($i + 1),
                'promo_code' => null,
                'discount_amount' => 0,
            ]);

            $product = $products[$i];
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $i + 1,
                'price' => $product->price,
            ]);

            $order->created_at = $dates[$i];
            $order->updated_at = $dates[$i];
            $order->save();
        }
    }
}
