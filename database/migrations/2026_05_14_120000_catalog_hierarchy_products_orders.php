<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        if (! Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unique(['category_id', 'product_id']);
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'care_difficulty')) {
                $table->enum('care_difficulty', ['easy', 'medium', 'hard'])->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('products', 'size')) {
                $table->enum('size', ['small', 'medium', 'large', 'extra_large'])->nullable()->after('care_difficulty');
            }
            if (! Schema::hasColumn('products', 'age_group')) {
                $table->enum('age_group', ['young', 'mature', 'old'])->nullable()->after('size');
            }
        });

        if (Schema::hasTable('category_product') && Schema::hasTable('products')) {
            $pairs = DB::table('products')->select('id', 'category_id')->whereNotNull('category_id')->get();
            foreach ($pairs as $row) {
                $exists = DB::table('category_product')
                    ->where('product_id', $row->id)
                    ->where('category_id', $row->category_id)
                    ->exists();
                if (! $exists) {
                    DB::table('category_product')->insert([
                        'category_id' => $row->category_id,
                        'product_id' => $row->id,
                    ]);
                }
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'yandex_pay_order_id')) {
                $table->string('yandex_pay_order_id')->nullable()->after('delivery_address');
            }
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('yandex_pay_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('orders', 'yandex_pay_order_id')) {
                $table->dropColumn('yandex_pay_order_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (['age_group', 'size', 'care_difficulty'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('category_product');

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
