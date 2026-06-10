<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLookupTables();
        $this->seedLookupTables();

        Schema::table('category_product', function (Blueprint $table) {
            if (! Schema::hasColumn('category_product', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('product_id');
            }
        });

        $this->migrateProductsToForeignKeys();
        $this->migrateOrdersToForeignKeys();
        $this->migrateMainImagesToProductImages();

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            foreach (['stock_status', 'care_difficulty', 'size', 'age_group', 'main_image'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'total_price')) {
                $table->dropColumn('total_price');
            }
            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'stock_status')) {
                $table->string('stock_status')->default('В наличии');
            }
            if (! Schema::hasColumn('products', 'care_difficulty')) {
                $table->string('care_difficulty')->nullable();
            }
            if (! Schema::hasColumn('products', 'size')) {
                $table->string('size')->nullable();
            }
            if (! Schema::hasColumn('products', 'age_group')) {
                $table->string('age_group')->nullable();
            }
            if (! Schema::hasColumn('products', 'main_image')) {
                $table->string('main_image')->nullable();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'total_price')) {
                $table->decimal('total_price', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('в обработке');
            }
        });

        if (Schema::hasColumn('products', 'stock_status_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('stock_status_id');
                $table->dropConstrainedForeignId('care_difficulty_id');
                $table->dropConstrainedForeignId('product_size_id');
                $table->dropConstrainedForeignId('age_group_id');
            });
        }

        if (Schema::hasColumn('orders', 'order_status_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('order_status_id');
            });
        }

        Schema::table('category_product', function (Blueprint $table) {
            if (Schema::hasColumn('category_product', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });

        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('age_groups');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('care_difficulties');
        Schema::dropIfExists('stock_statuses');
    }

    private function createLookupTables(): void
    {
        Schema::create('stock_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('care_difficulties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('age_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });

        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label', 64);
        });
    }

    private function seedLookupTables(): void
    {
        foreach ([
            ['code' => 'in_stock', 'label' => 'В наличии'],
            ['code' => 'on_order', 'label' => 'Под заказ'],
            ['code' => 'out_of_stock', 'label' => 'Закончился'],
        ] as $row) {
            DB::table('stock_statuses')->insertOrIgnore($row);
        }

        foreach ([
            ['code' => 'easy', 'label' => 'Лёгкий'],
            ['code' => 'medium', 'label' => 'Средний'],
            ['code' => 'hard', 'label' => 'Сложный'],
        ] as $row) {
            DB::table('care_difficulties')->insertOrIgnore($row);
        }

        foreach ([
            ['code' => 'small', 'label' => 'Маленький'],
            ['code' => 'medium', 'label' => 'Средний'],
            ['code' => 'large', 'label' => 'Большой'],
            ['code' => 'extra_large', 'label' => 'Очень большой'],
        ] as $row) {
            DB::table('product_sizes')->insertOrIgnore($row);
        }

        foreach ([
            ['code' => 'young', 'label' => 'Молодое'],
            ['code' => 'mature', 'label' => 'Взрослое'],
            ['code' => 'old', 'label' => 'Старое'],
        ] as $row) {
            DB::table('age_groups')->insertOrIgnore($row);
        }

        foreach ([
            ['code' => 'processing', 'label' => 'в обработке'],
            ['code' => 'shipped', 'label' => 'отправлен'],
            ['code' => 'completed', 'label' => 'выполнен'],
            ['code' => 'cancelled', 'label' => 'отменен'],
            ['code' => 'pending', 'label' => 'pending'],
        ] as $row) {
            DB::table('order_statuses')->insertOrIgnore($row);
        }
    }

    private function migrateProductsToForeignKeys(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('stock_status_id')->nullable()->after('quantity')->constrained('stock_statuses');
            $table->foreignId('care_difficulty_id')->nullable()->after('stock_status_id')->constrained('care_difficulties');
            $table->foreignId('product_size_id')->nullable()->after('care_difficulty_id')->constrained('product_sizes');
            $table->foreignId('age_group_id')->nullable()->after('product_size_id')->constrained('age_groups');
        });

        $stockMap = DB::table('stock_statuses')->pluck('id', 'label');
        $stockByCode = [
            'В наличии' => $stockMap['В наличии'] ?? null,
            'Под заказ' => $stockMap['Под заказ'] ?? null,
            'Закончился' => $stockMap['Закончился'] ?? null,
        ];

        $careMap = DB::table('care_difficulties')->pluck('id', 'code');
        $sizeMap = DB::table('product_sizes')->pluck('id', 'code');
        $ageMap = DB::table('age_groups')->pluck('id', 'code');

        foreach (DB::table('products')->get() as $product) {
            $updates = [
                'stock_status_id' => $stockByCode[$product->stock_status ?? ''] ?? DB::table('stock_statuses')->where('code', 'in_stock')->value('id'),
            ];
            if (! empty($product->care_difficulty)) {
                $updates['care_difficulty_id'] = $careMap[$product->care_difficulty] ?? null;
            }
            if (! empty($product->size)) {
                $updates['product_size_id'] = $sizeMap[$product->size] ?? null;
            }
            if (! empty($product->age_group)) {
                $updates['age_group_id'] = $ageMap[$product->age_group] ?? null;
            }
            DB::table('products')->where('id', $product->id)->update($updates);
        }

        if (Schema::hasColumn('products', 'category_id')) {
            foreach (DB::table('products')->whereNotNull('category_id')->get(['id', 'category_id']) as $row) {
                $exists = DB::table('category_product')
                    ->where('product_id', $row->id)
                    ->where('category_id', $row->category_id)
                    ->exists();
                if (! $exists) {
                    DB::table('category_product')->insert([
                        'category_id' => $row->category_id,
                        'product_id' => $row->id,
                        'is_primary' => true,
                    ]);
                } else {
                    DB::table('category_product')
                        ->where('product_id', $row->id)
                        ->where('category_id', $row->category_id)
                        ->update(['is_primary' => true]);
                }
            }
            foreach (DB::table('category_product')->select('product_id')->distinct()->pluck('product_id') as $productId) {
                $hasPrimary = DB::table('category_product')
                    ->where('product_id', $productId)
                    ->where('is_primary', true)
                    ->exists();
                if (! $hasPrimary) {
                    $first = DB::table('category_product')->where('product_id', $productId)->orderBy('category_id')->first();
                    if ($first) {
                        DB::table('category_product')
                            ->where('category_id', $first->category_id)
                            ->where('product_id', $productId)
                            ->update(['is_primary' => true]);
                    }
                }
            }
        }
    }

    private function migrateOrdersToForeignKeys(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_status_id')->nullable()->after('user_id')->constrained('order_statuses');
        });

        $statusMap = DB::table('order_statuses')->pluck('id', 'label');
        $legacy = [
            'pending' => 'pending',
            'completed' => 'выполнен',
            'cancelled' => 'отменен',
        ];

        foreach (DB::table('orders')->get() as $order) {
            $label = $order->status ?? 'в обработке';
            if (isset($legacy[$label])) {
                $label = $legacy[$label];
            }
            if ($label === 'отменён') {
                $label = 'отменен';
            }
            $statusId = $statusMap[$label] ?? $statusMap['в обработке'] ?? DB::table('order_statuses')->where('code', 'processing')->value('id');

            $updates = ['order_status_id' => $statusId];
            if (Schema::hasColumn('orders', 'total_price') && empty($order->total_amount) && ! empty($order->total_price)) {
                $updates['total_amount'] = $order->total_price;
            }
            DB::table('orders')->where('id', $order->id)->update($updates);
        }
    }

    private function migrateMainImagesToProductImages(): void
    {
        if (! Schema::hasColumn('products', 'main_image')) {
            return;
        }

        foreach (DB::table('products')->whereNotNull('main_image')->where('main_image', '!=', '')->get() as $product) {
            $exists = DB::table('product_images')
                ->where('product_id', $product->id)
                ->where('image_url', $product->main_image)
                ->exists();
            if (! $exists) {
                $minOrder = DB::table('product_images')->where('product_id', $product->id)->min('order');
                $order = $minOrder === null ? 0 : min(0, (int) $minOrder - 1);
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'image_url' => $product->main_image,
                    'order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
