<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $soilsCategoryId = Category::query()->where('slug', 'soils')->value('id');

        PromoCode::query()->updateOrCreate(
            ['code' => 'Grunt4100'],
            [
                'type' => PromoCode::TYPE_BUNDLE_FREE,
                'category_id' => $soilsCategoryId,
                'product_id' => null,
                'buy_quantity' => 3,
                'free_quantity' => 1,
                'min_order_amount' => null,
                'discount_percent' => null,
                'description' => 'Каждый 4-й грунт в корзине — бесплатно (скидка 100%)',
                'active' => true,
            ]
        );
    }
}
