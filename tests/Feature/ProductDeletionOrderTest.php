<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductDeletionOrderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cannot_delete_product_in_active_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['quantity' => 10]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => OrderStatus::idForLabel('в обработке'),
            'delivery_address' => 'г. Иркутск, тест',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $response = $this->actingAs($admin)->delete('/admin/products/'.$product->id);

        $response->assertRedirect();
        $response->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);
    }

    public function test_order_item_keeps_name_when_product_deleted_after_cancelled_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['quantity' => 10, 'name' => 'Орхидея тест']);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => OrderStatus::idForLabel('отменен'),
            'delivery_address' => 'г. Иркутск, тест',
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $this->actingAs($admin)->delete('/admin/products/'.$product->id)->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $item->refresh();
        $this->assertSame('Орхидея тест', $item->display_name);
        $this->assertNull($item->product_id);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'product_name' => 'Орхидея тест',
        ]);
    }
}
