<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StockStatus;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private function catIds(array $slugs): array
    {
        return Category::whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function upsertProduct(array $data, array $categorySlugs): Product
    {
        $ids = $this->catIds($categorySlugs);

        $product = Product::updateOrCreate(
            ['article' => $data['article']],
            collect($data)->only([
                'name', 'slug', 'price', 'description', 'quantity',
            ])->all()
        );

        $product->stock_status = $data['stock_status'] ?? 'В наличии';
        $product->care_difficulty = $data['care_difficulty'] ?? null;
        $product->size = $data['size'] ?? null;
        $product->age_group = $data['age_group'] ?? null;
        if (! empty($data['main_image'])) {
            $product->main_image = $data['main_image'];
        }
        $product->save();

        if ($ids) {
            Product::syncCategoryPivot($product, $ids);
        }

        return $product;
    }

    public function run(): void
    {
        StockStatus::query()->updateOrCreate(
            ['code' => 'in_stock'],
            ['label' => 'В наличии', 'is_active' => true, 'sort_order' => 1]
        );
        StockStatus::query()->updateOrCreate(
            ['code' => 'out_of_stock'],
            ['label' => 'Закончился', 'is_active' => true, 'sort_order' => 2]
        );

        $p1 = $this->upsertProduct([
            'name' => 'Фаленопсис White',
            'slug' => 'phalaenopsis-white',
            'article' => 'P-001',
            'price' => 1890,
            'description' => 'Компактная орхидея с длительным цветением.',
            'stock_status' => 'В наличии',
            'quantity' => 8,
            'main_image' => '/images/anturium.jpg',
            'care_difficulty' => 'medium',
            'size' => 'small',
            'age_group' => 'mature',
        ], ['orchids', 'blooming']);

        ProductImage::updateOrCreate(
            ['product_id' => $p1->id, 'image_url' => '/images/anturium2.jpg'],
            ['product_id' => $p1->id, 'image_url' => '/images/anturium2.jpg', 'order' => 1]
        );

        $this->upsertProduct([
            'name' => 'Монстера Deliciosa',
            'slug' => 'monstera-deliciosa',
            'article' => 'P-002',
            'price' => 2490,
            'description' => 'Крупные листья, яркий акцент интерьера.',
            'stock_status' => 'В наличии',
            'quantity' => 5,
            'main_image' => '/images/4.png',
            'care_difficulty' => 'easy',
            'size' => 'large',
            'age_group' => 'young',
        ], ['monstera', 'decorative-leaf']);

        $this->upsertProduct([
            'name' => 'Эхеверия Lola',
            'slug' => 'echeveria-lola',
            'article' => 'P-003',
            'price' => 590,
            'description' => 'Розетка пастельных оттенков, подходит для подоконника.',
            'stock_status' => 'В наличии',
            'quantity' => 22,
            'main_image' => '/images/5.png',
            'care_difficulty' => 'easy',
            'size' => 'small',
            'age_group' => 'young',
        ], ['echeveria', 'succulents-and-cacti']);

        $this->upsertProduct([
            'name' => 'Сансевиерия Laurentii',
            'slug' => 'sansevieria-laurentii',
            'article' => 'P-004',
            'price' => 1290,
            'description' => 'Неприхотливое растение, переносит пересушку.',
            'stock_status' => 'В наличии',
            'quantity' => 4,
            'main_image' => '/images/apip.png',
            'care_difficulty' => 'easy',
            'size' => 'medium',
            'age_group' => 'mature',
        ], ['sansevieria', 'easy-care-plants']);

        $this->upsertProduct([
            'name' => 'Фикус lyrata',
            'slug' => 'ficus-lyrata-seed',
            'article' => 'P-005',
            'price' => 3490,
            'description' => 'Крупномер с виолончельными листьями.',
            'stock_status' => 'Закончился',
            'quantity' => 0,
            'main_image' => '/images/ficus.png',
            'care_difficulty' => 'hard',
            'size' => 'extra_large',
            'age_group' => 'mature',
        ], ['ficus', 'palms-large']);

        $this->upsertProduct([
            'name' => 'Керамическое кашпо 18 см',
            'slug' => 'ceramic-pot-18',
            'article' => 'A-010',
            'price' => 890,
            'description' => 'Дренажное отверстие, матовая глазурь.',
            'stock_status' => 'В наличии',
            'quantity' => 30,
            'main_image' => '/images/2.png',
            'care_difficulty' => null,
            'size' => null,
            'age_group' => null,
        ], ['pots-supplies', 'accessories']);

        $this->upsertProduct([
            'name' => 'Универсальный грунт 5 л',
            'slug' => 'soil-universal-5l',
            'article' => 'A-011',
            'price' => 420,
            'description' => 'Для большинства комнатных культур.',
            'stock_status' => 'В наличии',
            'quantity' => 40,
            'main_image' => '/images/6.jpg',
            'care_difficulty' => null,
            'size' => null,
            'age_group' => null,
        ], ['soils', 'accessories']);

        $this->upsertProduct([
            'name' => 'Грунт для суккулентов 3 л',
            'slug' => 'soil-succulent-3l',
            'article' => 'A-012',
            'price' => 380,
            'description' => 'С дренажем, для кактусов и суккулентов.',
            'stock_status' => 'В наличии',
            'quantity' => 35,
            'main_image' => '/images/6.jpg',
            'care_difficulty' => null,
            'size' => null,
            'age_group' => null,
        ], ['soils']);

        $this->upsertProduct([
            'name' => 'Торфяная смесь 5 л',
            'slug' => 'soil-peat-5l',
            'article' => 'A-013',
            'price' => 450,
            'description' => 'Рыхлая смесь для рассады и пересадки.',
            'stock_status' => 'В наличии',
            'quantity' => 30,
            'main_image' => '/images/6.jpg',
            'care_difficulty' => null,
            'size' => null,
            'age_group' => null,
        ], ['soils']);
    }
}
